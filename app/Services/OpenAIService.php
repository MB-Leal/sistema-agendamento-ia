<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppMessage;
use App\Models\User;      // 🚀 Importado para buscar o cadastro do cliente
use App\Models\Reserva;   // 🚀 Importado para buscar agendamentos ativos
use Carbon\Carbon;

class OpenAIService
{
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY') ?? '';
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Gera a resposta da IA baseada no perfil do cliente, reservas ativas e histórico
     */
    public function getAIResponse(string $phoneContact, string $customerMessage): string
    {
        if (empty($this->apiKey)) {
            Log::error("OpenAI API Key não configurada no arquivo .env");
            return "Desculpe, nosso sistema está passando por uma manutenção rápida.";
        }

        // 🛡️ 1. SEGMENTAÇÃO E AGREGADO DE DADOS DO BANCO DE DADOS
        $nomeCliente = null;
        $contextoCliente = "STATUS: Cliente Novo (Sem cadastro prévio no banco de dados).\nAction: Pergunte educadamente o nome dele para iniciar o atendimento.";
        $contextoReservas = "RESERVAS ATIVAS: O cliente não possui nenhuma reserva agendada no momento.";

        try {
            // Busca o usuário pelo telefone cadastrado (removendo caracteres especiais se houver)
            // Adapte o campo 'telefone' ou 'phone' conforme a coluna real da sua tabela users
            $user = User::where('phone', $phoneContact)
                        ->orWhere('phone', 'like', '%' . substr($phoneContact, 4) . '%')
                        ->first();

            if ($user) {
                $nomeCliente = $user->name;
                $contextoCliente = "STATUS: Cliente Cadastrado Antigo.\nNome do Cliente: {$nomeCliente}.\nAction: Dê as boas-vindas calorosas usando o nome dele, demonstrando que o sistema o reconhece (Ex: 'Bom ter você de volta, {$nomeCliente}!').";

                // Busca as reservas ativas deste cliente que ainda vão acontecer (>= que agora)
                $reservasAtivas = Reserva::where('user_id', $user->id)
                    ->where('status', 'confirmed') // Ajuste o enum conforme seu banco
                    ->where('data_reserva', '>=', Carbon::today()->toDateString())
                    ->orderBy('data_reserva', 'asc')
                    ->get();

                if ($reservasAtivas->count() > 0) {
                    $contextoReservas = "RESERVAS ATIVAS ENCONTRADAS:\n";
                    foreach ($reservasAtivas as $reserva) {
                        $dataCarb = Carbon::parse($reserva->data_reserva . ' ' . $reserva->hora_inicio);
                        $podeCancelar = Carbon::now()->diffInHours($dataCarb, false) >= 24;
                        
                        $contextoReservas .= "- Reserva ID: {$reserva->id} | Data: " . Carbon::parse($reserva->data_reserva)->format('d/m/Y') . " às {$reserva->hora_inicio}h | ";
                        $contextoReservas .= $podeCancelar 
                            ? "STATUS CANCELAMENTO: Permitido (Faltam mais de 24 horas. Pode cancelar ou trocar sem perder o sinal).\n" 
                            : "STATUS CANCELAMENTO: Bloqueado (Faltam menos de 24 horas. Avise que políticas da Arena não permitem estorno do sinal por proximidade do horário).\n";
                    }
                }
            }
        } catch (\Exception $dbEx) {
            Log::error("Erro ao cruzar dados de User/Reservas para a OpenAI: " . $dbEx->getMessage());
        }

        // 2. PROMPT MESTRE DINÂMICO COM INJEÇÃO DE CONTEXTO REAL
        $systemPrompt = "Você é a assistente virtual inteligente e atendente oficial da Arena Elizeu.\n" .
                        "Seu objetivo é ser extremamente educada, prestativa e rápida para gerenciar agendamentos e tirar dúvidas.\n\n" .
                        "📌 CONTEXTO REAL DO CLIENTE ATUAL (BUSCADO NO BANCO DE DADOS):\n" .
                        "{$contextoCliente}\n" .
                        "{$contextoReservas}\n\n" .
                        "🏢 REGRAS DA ARENA ELIZEU:\n" .
                        "1. Possuímos APENAS UMA quadra de futebol soccer (society). Agendamentos são direto para ela.\n" .
                        "2. Localização: Link do Google Maps: https://maps.app.goo.gl/mEkWThR4gkot25RD6 \n" .
                        "3. Sinal de Reserva: R$ 50,00 obrigatórios via PIX para garantir horários novos.\n" .
                        "4. Regra de Cancelamento/Troca: O cliente pode cancelar ou alterar o horário livremente desde que faça com no MÍNIMO 24 HORAS DE ANTECEDÊNCIA. Caso contrário, ele perde o valor do sinal devido à reserva da quadra.\n\n" .
                        "🎯 REGRA CRUCIAL DE PAGAMENTO (MERCADO PAGO):\n" .
                        "- Quando o usuário confirmar o Nome, o Dia, o Horário e concordar em pagar os R$ 50,00 de sinal por PIX, você deve aceitar e incluir obrigatoriamente a tag exata [GERAR_PIX:50.00] colada ao final do texto.\n\n" .
                        "💬 REGRAS DE CONVERSAÇÃO:\n" .
                        "- Respostas curtas (máximo 3 frases).\n" .
                        "Hoje é dia " . date('d/m/Y') . " (Horário atual: " . date('H:i') . ").";

        // 3. RECUPERAÇÃO DA MEMÓRIA TEXTUAL RECENTE (Histórico das últimas mensagens isoladas por número)
        $historyMessages = [];
        try {
            $targetJid = $phoneContact . '@s.whatsapp.net';
            $rawLogs = WhatsAppMessage::where('remote_jid', $targetJid)
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get()
                ->reverse();

            foreach ($rawLogs as $log) {
                $role = $log->from_me ? 'assistant' : 'user';
                $historyMessages[] = ['role' => $role, 'content' => $log->message];
            }
        } catch (\Exception $e) {
            Log::error("Erro ao recuperar histórico de mensagens: " . $e->getMessage());
        }

        // 4. MONTAGEM DO PAYLOAD FINAL
        $messagesPayload = [];
        $messagesPayload[] = ['role' => 'system', 'content' => $systemPrompt];

        foreach ($historyMessages as $pastMessage) {
            $messagesPayload[] = $pastMessage;
        }

        $lastSaved = end($historyMessages);
        if (!$lastSaved || $lastSaved['content'] !== $customerMessage) {
            $messagesPayload[] = ['role' => 'user', 'content' => $customerMessage];
        }

        Log::info("Chamando OpenAI Cognitiva para o número {$phoneContact}. Cadastro: " . ($nomeCliente ? 'Sim' : 'Não'));

        // 5. DISPARO PARA A API DA OPENAI
        $response = Http::withToken($this->apiKey)
            ->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => $messagesPayload,
                'temperature' => 0.3
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $aiText = $data['choices'][0]['message']['content'] ?? '';
            Log::info("Resposta gerada pela OpenAI com sucesso!");
            return trim($aiText);
        }

        Log::error("Erro na API da OpenAI: " . $response->body());
        return "Desculpe, tive um probleminha para processar sua mensagem agora. Pode repetir?";
    }
}