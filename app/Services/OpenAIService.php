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
            // AQUI FOI APLICADA A CORREÇÃO DO WHATSAPP_CONTACT DO PASSO ANTERIOR
            $user = User::where('whatsapp_contact', $phoneContact)
                ->orWhere('whatsapp_contact', 'like', '%' . substr($phoneContact, -8))
                ->first();

            if ($user) {
                if (isset($user->chat_human_mode) && $user->chat_human_mode == 1) {
                    Log::info("Cliente {$phoneContact} está em atendimento humano. IA silenciada.");
                    return "[HUMANO_ATIVO]";
                }

                $nomeCliente = $user->name;
                $contextoCliente = "STATUS: Cliente Cadastrado Antigo.\nNome: {$nomeCliente}.\nAction: Demonstre que o reconhece.";

                $reservasAtivas = Reserva::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('date', '>=', Carbon::today()->toDateString()) // <-- Mude aqui
                    ->orderBy('date', 'asc') // <-- Mude aqui
                    ->get();

                if ($reservasAtivas->count() > 0) {
                    $contextoReservas = "RESERVAS ENCONTRADAS ATIVAS:\n";
                    foreach ($reservasAtivas as $reserva) {
                        $dataCarb = Carbon::parse($reserva->date . ' ' . $reserva->start_time); // Ajustado para date e start_time
                        $podeCancelar = Carbon::now()->diffInHours($dataCarb, false) >= 24;

                        $contextoReservas .= "- Reserva ID: {$reserva->id} | Data: " . Carbon::parse($reserva->date)->format('d/m/Y') . " às " . substr($reserva->start_time, 0, 5) . "h | Status do Sistema: {$reserva->status} | ";
                        $contextoReservas .= $podeCancelar ? "CANCELAMENTO: Permitido (+24h de antecedência).\n" : "CANCELAMENTO: Proibido (Menos de 24h).\n";
                    }
                }
            }
        } catch (\Exception $dbEx) {
            Log::error("Erro no cruzamento de dados: " . $dbEx->getMessage());
        }

        // 2. PROMPT MESTRE
        $systemPrompt = "Você é a assistente virtual inteligente e atendente oficial da Arena Elizeu.\n" .
            "Você DEVE responder APENAS perguntas sobre agendamentos, horários, cancelamentos e o funcionamento da Arena Elizeu.\n\n" .
            "📌 CONTEXTO DO CLIENTE ATUAL:\n{$contextoCliente}\n{$contextoReservas}\n\n" .
            "⚠️ REGRA DE OURO PARA AGENDAMENTOS:\n" .
            "Você NUNCA pode confirmar um horário sem ANTES usar a ferramenta 'verificar_disponibilidade'. Se a ferramenta disser que está ocupado, peça desculpas e sugira outro horário. Não invente disponibilidades!\n\n" .
            "🏢 POLÍTICAS DE PAGAMENTO FLEXÍVEIS DA ARENA:\n" .
            "1. Sinal Aberto: Valor padrão R$ 50,00, mas aceite o que o cliente propor.\n" .
            "2. Clientes Antigos / 'Pagar na Hora': Aceite e use a tag [RESERVA_PENDENTE:0.00].\n" .
            "3. Dinheiro ou Cartão Presencial: Explique a regra e use a tag [RESERVA_PENDENTE:0.00].\n\n" .
            "🎯 PIX MERCADO PAGO:\n- Se for PIX Copia e Cola, use a tag [GERAR_PIX:VALOR] ao final (Ex: [GERAR_PIX:50.00]).\n\n" .
            "⚠️ ATENDIMENTO HUMANO: Se irritado ou pedir humano, use a tag [ATIVAR_HUMANO].\n\n" .
            "❌ CANCELAMENTO: Mais de 24h: [CANCELAR_RESERVA]. Menos de 24h: Proibido.\n\n" .
            "Hoje é dia " . date('d/m/Y') . " (Horário: " . date('H:i') . "). Responda de forma natural, amigável e curta.";

        // 3. RECUPERAÇÃO DA MEMÓRIA
        $historyMessages = [];
        try {
            $targetJid = $phoneContact . '@s.whatsapp.net';
            $rawLogs = WhatsAppMessage::where('remote_jid', $targetJid)
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get()
                ->reverse();

            foreach ($rawLogs as $log) {
                if ($log->message === '[HUMANO_ATIVO]') return "[HUMANO_ATIVO]";
                $role = $log->from_me ? 'assistant' : 'user';
                $historyMessages[] = ['role' => $role, 'content' => $log->message];
            }
        } catch (\Exception $e) {
            Log::error("Erro no histórico: " . $e->getMessage());
        }

        $messagesPayload = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($historyMessages as $pastMessage) {
            $messagesPayload[] = $pastMessage;
        }

        $lastSaved = end($historyMessages);
        if (!$lastSaved || $lastSaved['content'] !== $customerMessage) {
            $messagesPayload[] = ['role' => 'user', 'content' => $customerMessage];
        }

        // 4. DEFINIÇÃO DA FERRAMENTA (TOOLS) PARA A IA CONSULTAR O BANCO
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'verificar_disponibilidade',
                    'description' => 'Verifica no banco de dados se um horário específico está disponível para locação.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'string', 'description' => 'A data do agendamento no formato YYYY-MM-DD'],
                            'hora' => ['type' => 'string', 'description' => 'A hora do agendamento no formato HH:MM']
                        ],
                        'required' => ['data', 'hora']
                    ]
                ]
            ]
        ];

        // 5. PRIMEIRA CHAMADA OPENAI
        $response = Http::withToken($this->apiKey)->post($this->apiUrl, [
            'model' => 'gpt-4o-mini',
            'messages' => $messagesPayload,
            'tools' => $tools,
            'temperature' => 0.2
        ]);

        if (!$response->successful()) {
            return "Desculpe, tive um probleminha técnico. Pode repetir?";
        }

        $responseData = $response->json();
        $message = $responseData['choices'][0]['message'];

        // 6. VERIFICA SE A IA DECIDIU CHAMAR A FERRAMENTA
        if (isset($message['tool_calls'])) {
            $messagesPayload[] = $message; // Adiciona a decisão da IA no histórico temporário

            foreach ($message['tool_calls'] as $toolCall) {
                if ($toolCall['function']['name'] === 'verificar_disponibilidade') {
                    $args = json_decode($toolCall['function']['arguments'], true);

                    // Chama a função interna do PHP para bater no banco de dados
                    $resultadoDB = $this->checarBancoDeDados($args['data'], $args['hora']);

                    // Devolve a resposta do banco para a IA
                    $messagesPayload[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => $resultadoDB
                    ];
                }
            }

            // 7. SEGUNDA CHAMADA OPENAI (Agora a IA tem a resposta do banco e pode responder ao cliente)
            $secondResponse = Http::withToken($this->apiKey)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => $messagesPayload,
                'temperature' => 0.2
            ]);

            if ($secondResponse->successful()) {
                $secondData = $secondResponse->json();
                return trim($secondData['choices'][0]['message']['content'] ?? '');
            }
        }

        // Se não precisou chamar ferramenta, retorna a mensagem normal
        return trim($message['content'] ?? '');
    }

    /**
     * Função auxiliar para checar a disponibilidade no banco real
     */
    private function checarBancoDeDados($data, $hora): string
    {
        try {
            // Formata a hora para bater com o banco (ex: 12:00 -> 12:00:00)
            $horaFormatada = Carbon::parse($hora)->format('H:i:s');

            $ocupado = Reserva::where('date', $data)
                ->where('start_time', $horaFormatada)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($ocupado) {
                return "O horário das {$hora} no dia {$data} está OCUPADO. Diga ao cliente que já existe uma reserva e peça para escolher outro horário.";
            }

            return "O horário das {$hora} no dia {$data} está LIVRE. Você pode prosseguir com a confirmação e gerar a tag de pagamento.";
        } catch (\Exception $e) {
            Log::error("Erro na verificação de disponibilidade: " . $e->getMessage());
            return "Ocorreu um erro no sistema ao verificar. Peça para o cliente aguardar um momento.";
        }
    }
}
