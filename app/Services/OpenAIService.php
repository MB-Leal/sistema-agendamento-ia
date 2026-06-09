<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppMessage;
use App\Models\User;      
use App\Models\Reserva;   
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

    public function getAIResponse(string $phoneContact, string $customerMessage): string
    {
        if (empty($this->apiKey)) {
            Log::error("OpenAI API Key não configurada no arquivo .env");
            return "Desculpe, nosso sistema está passando por uma manutenção rápida.";
        }

        // 1. ANÁLISE DE PERFIL E RESERVAS NO BANCO
        $nomeCliente = null;
        $contextoCliente = "STATUS: Cliente Novo.\nAction: Pergunte o nome para iniciar.";
        $contextoReservas = "RESERVAS ATIVAS: O cliente não possui agendamentos futuros.";

        try {
            $user = User::where('phone_number', $phoneContact)
                        ->orWhere('telefone', $phoneContact)
                        ->first();

            if ($user) {
                // 🚨 VERIFICAÇÃO DE TRANSBORDO HUMANO: Se o usuário já estiver em modo humano, a IA avisa o log
                if (isset($user->chat_human_mode) && $user->chat_human_mode == 1) {
                    Log::info("Cliente {$phoneContact} está em atendimento humano. IA silenciada.");
                    return "[HUMANO_ATIVO]";
                }

                $nomeCliente = $user->name;
                $contextoCliente = "STATUS: Cliente Cadastrado Antigo.\nNome: {$nomeCliente}.\nAction: Demonstre que o reconhece.";

                // Busca reservas futuras
                $reservasAtivas = Reserva::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'confirmed']) 
                    ->where('data_reserva', '>=', Carbon::today()->toDateString())
                    ->orderBy('data_reserva', 'asc')
                    ->get();

                if ($reservasAtivas->count() > 0) {
                    $contextoReservas = "RESERVAS ENCONTRADAS ATIVAS:\n";
                    foreach ($reservasAtivas as $reserva) {
                        $dataCarb = Carbon::parse($reserva->data_reserva . ' ' . $reserva->hora_inicio);
                        $podeCancelar = Carbon::now()->diffInHours($dataCarb, false) >= 24;
                        
                        $contextoReservas .= "- Reserva ID: {$reserva->id} | Data: " . Carbon::parse($reserva->data_reserva)->format('d/m/Y') . " às " . substr($reserva->hora_inicio, 0, 5) . "h | Status do Sistema: {$reserva->status} | ";
                        $contextoReservas .= $podeCancelar ? "CANCELAMENTO: Permitido (+24h de antecedência).\n" : "CANCELAMENTO: Proibido (Menos de 24h).\n";
                    }
                }
            }
        } catch (\Exception $dbEx) {
            Log::error("Erro no cruzamento de dados: " . $dbEx->getMessage());
        }

        // 2. PROMPT MESTRE COM AS NOVAS DIRETRIZES FLEXÍVEIS
        $systemPrompt = "Você é a assistente virtual inteligente e atendente oficial da Arena Elizeu.\n" .
                        "Você DEVE responder APENAS perguntas sobre agendamentos, horários, cancelamentos e o funcionamento da Arena Elizeu. Se o cliente perguntar qualquer assunto fora disso, recuse educadamente.\n\n" .
                        "📌 CONTEXTO DO CLIENTE ATUAL:\n" .
                        "{$contextoCliente}\n" .
                        "{$contextoReservas}\n\n" .
                        "🏢 POLÍTICAS DE PAGAMENTO FLEXÍVEIS DA ARENA:\n" .
                        "1. Sinal Aberto: O valor padrão do sinal é R$ 50,00, mas você pode aceitar QUALQUER VALOR que o cliente propor enviar como sinal.\n" .
                        "2. Clientes Antigos / Parceiros / 'Pagar na Hora': Se o cliente disser que é amigo do dono, parceiro antigo, ou disser que vai 'pagar na hora do jogo', aceite imediatamente! Diga que vai deixar o horário reservado como pendente e que o gestor confirmará no sistema. Para esse caso, use a tag técnica final [RESERVA_PENDENTE:0.00].\n" .
                        "3. Sinal em Dinheiro Presencial: Se ele quiser pagar o sinal em dinheiro, explique que ele deve ir presencialmente até o estabelecimento pagar ao gestor para ter o horário confirmado. Use a tag final [RESERVA_PENDENTE:0.00].\n" .
                        "4. Sinal no Cartão de Crédito/Débito: Explique que a maquininha fica fixa na Arena presencial. Ele deve ir até lá pagar o sinal para o gestor confirmar. Use a tag final [RESERVA_PENDENTE:0.00].\n\n" .
                        "🎯 REGRA DE INTEGRAÇÃO DO PIX MERCADO PAGO:\n" .
                        "- Se o cliente fechar o agendamento e optar por pagar o sinal via PIX Copia e Cola, use o valor acordado (seja R$ 50.00 ou outro valor combinado) e anexe obrigatoriamente a tag exata: [GERAR_PIX:VALOR] ao final do texto (Exemplo: [GERAR_PIX:50.00] ou [GERAR_PIX:30.00]).\n\n" .
                        "⚠️ SOLICITAÇÃO DE ATENDIMENTO HUMANO:\n" .
                        "- Se o cliente disser explicitamente 'quero falar com um humano', 'chama o atendente', ou demonstrar irritação insolúvel, diga que está repassando para o suporte humano e termine OBRIGATORIAMENTE com a tag: [ATIVAR_HUMANO].\n\n" .
                        "❌ POLÍTICA DE CANCELAMENTO:\n" .
                        "- Se o cliente pedir para cancelar: verifique no histórico acima se faltam mais de 24 horas para o jogo. Se tiver MAIS de 24 horas, diga que cancelou o horário no sistema, mas avise que a devolução/estorno do valor do sinal deverá ser tratada diretamente com um atendente humano. Anexe a tag final: [CANCELAR_RESERVA]. Se faltar MENOS de 24 horas, informe que pelas políticas da Arena, não há direito a estorno ou cancelamento.\n\n" .
                        "Hoje é dia " . date('d/m/Y') . " (Horário: " . date('H:i') . "). Responda com mensagens curtas (até 3 frases).";

        // 3. RECUPERAÇÃO DA MEMÓRIA TEXTUAL
        $historyMessages = [];
        try {
            $targetJid = $phoneContact . '@s.whatsapp.net';
            $rawLogs = WhatsAppMessage::where('remote_jid', $targetJid)
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get()
                ->reverse();

            foreach ($rawLogs as $log) {
                // Se a última mensagem salvou que o humano está ativo, para o fluxo aqui
                if ($log->message === '[HUMANO_ATIVO]') {
                    return "[HUMANO_ATIVO]";
                }
                $role = $log->from_me ? 'assistant' : 'user';
                $historyMessages[] = ['role' => $role, 'content' => $log->message];
            }
        } catch (\Exception $e) {
            Log::error("Erro no histórico: " . $e->getMessage());
        }

        $messagesPayload = [];
        $messagesPayload[] = ['role' => 'system', 'content' => $systemPrompt];
        foreach ($historyMessages as $pastMessage) {
            $messagesPayload[] = $pastMessage;
        }

        $lastSaved = end($historyMessages);
        if (!$lastSaved || $lastSaved['content'] !== $customerMessage) {
            $messagesPayload[] = ['role' => 'user', 'content' => $customerMessage];
        }

        // 4. CHAMADA OPENAI
        $response = Http::withToken($this->apiKey)->post($this->apiUrl, [
            'model' => 'gpt-4o-mini',
            'messages' => $messagesPayload,
            'temperature' => 0.2
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return trim($data['choices'][0]['message']['content'] ?? '');
        }

        return "Desculpe, tive um probleminha técnico. Pode repetir?";
    }
}