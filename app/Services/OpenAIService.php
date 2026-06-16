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
        $contextoCliente = "STATUS: Cliente Novo.\nAction: Dê as boas-vindas e pergunte o nome para iniciar o atendimento e o cadastro.";
        $contextoReservas = "RESERVAS ATIVAS: O cliente não possui agendamentos futuros.";

        try {
            $user = User::where('whatsapp_contact', $phoneContact)
                ->orWhere('whatsapp_contact', 'like', '%' . substr($phoneContact, -8))
                ->first();

            if ($user) {
                if (isset($user->chat_human_mode) && $user->chat_human_mode == 1) {
                    Log::info("Cliente {$phoneContact} está em atendimento humano. IA silenciada.");
                    return "[HUMANO_ATIVO]";
                }

                $nomeCliente = $user->name;
                $contextoCliente = "STATUS: Cliente Cadastrado Antigo.\nNome: {$nomeCliente}.\nAction: Demonstre que o reconhece e pergunte como pode ajudar hoje.";

                $reservasAtivas = Reserva::where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('date', '>=', Carbon::today()->toDateString())
                    ->orderBy('date', 'asc')
                    ->get();

                if ($reservasAtivas->count() > 0) {
                    $contextoReservas = "RESERVAS ENCONTRADAS ATIVAS:\n";
                    foreach ($reservasAtivas as $reserva) {
                        // CORREÇÃO DO ERRO 'Double date specification'
                        $dataOnly = Carbon::parse($reserva->date)->format('Y-m-d');
                        $timeOnly = Carbon::parse($reserva->start_time)->format('H:i:s');
                        $dataCarb = Carbon::parse($dataOnly . ' ' . $timeOnly);
                        
                        $podeCancelar = Carbon::now()->diffInHours($dataCarb, false) >= 24;

                        $contextoReservas .= "- Reserva ID: {$reserva->id} | Data: " . Carbon::parse($reserva->date)->format('d/m/Y') . " às " . substr($reserva->start_time, 0, 5) . "h | Status do Sistema: {$reserva->status} | ";
                        $contextoReservas .= $podeCancelar ? "CANCELAMENTO: Permitido (+24h de antecedência).\n" : "CANCELAMENTO: Proibido (Menos de 24h).\n";
                    }
                }
            }
        } catch (\Exception $dbEx) {
            Log::error("Erro no cruzamento de dados: " . $dbEx->getMessage());
        }

        // 2. PROMPT MESTRE RESTRITO (STATE MACHINE)
        $systemPrompt = "Você é a assistente virtual inteligente e atendente oficial da Arena Elizeu.\n" .
            "Você DEVE responder APENAS perguntas sobre agendamentos, horários, cancelamentos e o funcionamento da Arena Elizeu.\n\n" .
            "📌 CONTEXTO DO CLIENTE ATUAL:\n{$contextoCliente}\n{$contextoReservas}\n\n" .
            "========================================\n" .
            "🚨 FLUXO OBRIGATÓRIO DE AGENDAMENTO (Siga rigorosamente os passos abaixo):\n" .
            "PASSO 1: Pergunte qual data e horário o cliente deseja.\n" .
            "PASSO 2: Assim que o cliente disser a data/hora, OBRIGATORIAMENTE use a ferramenta 'verificar_disponibilidade'. Não invente vagas.\n" .
            "PASSO 3: Se estiver OCUPADO, informe e sugira outro horário. Se estiver LIVRE, avise que está livre e vá para o Passo 4.\n" .
            "PASSO 4: Informe que para garantir a reserva é necessário um SINAL (sugira R$ 50,00, mas pergunte se ele concorda ou prefere dar outro valor). Pergunte COMO ele deseja pagar o sinal: via PIX (envio a chave Copia e Cola aqui), ou em Dinheiro/Cartão (neste caso, informe que ele deve ir presencialmente na Arena pagar antes do jogo).\n" .
            "PASSO 5: AGUARDE o cliente responder a forma de pagamento e o valor.\n" .
            "PASSO 6 (FINALIZAÇÃO): APENAS DEPOIS que o cliente confirmar o pagamento e o valor, insira a tag apropriada no final da sua mensagem.\n" .
            "========================================\n\n" .
            "💲 TAGS DE SISTEMA E REGRAS DE CANCELAMENTO (Invisíveis para o cliente, use apenas no PASSO 6):\n" .
            "- Se o cliente escolheu pagar via PIX: Gere a tag [GERAR_PIX:VALOR:DATA:HORA] (Ex: [GERAR_PIX:50.00:2026-06-16:08:00]). Informe ao cliente que você está enviando a chave PIX e que o agendamento será confirmado automaticamente APÓS o pagamento. Aproveite para avisar que em caso de cancelamento com mais de 24h de antecedência o sinal é reembolsado; com menos de 24h, o valor do sinal não é devolvido.\n" .
            "- Se o cliente escolheu pagar via DINHEIRO ou CARTÃO: Gere a tag [RESERVA_PENDENTE:VALOR:DATA:HORA] (Ex: [RESERVA_PENDENTE:50.00:2026-06-16:08:00]). Informe que a reserva foi anotada e ficará aguardando o pagamento presencial.\n\n" .
            "⚠️ ATENDIMENTO HUMANO: Se irritado ou pedir humano, use a tag [ATIVAR_HUMANO].\n" .
            "❌ CANCELAMENTO: Mais de 24h: [CANCELAR_RESERVA]. Menos de 24h: Avise que é possível cancelar, mas sem reembolso do sinal.\n\n" .
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
            $messagesPayload[] = $message;

            foreach ($message['tool_calls'] as $toolCall) {
                if ($toolCall['function']['name'] === 'verificar_disponibilidade') {
                    $args = json_decode($toolCall['function']['arguments'], true);
                    $resultadoDB = $this->checarBancoDeDados($args['data'], $args['hora']);

                    $messagesPayload[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => $resultadoDB
                    ];
                }
            }

            // 7. SEGUNDA CHAMADA OPENAI
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

        return trim($message['content'] ?? '');
    }

    private function checarBancoDeDados($data, $hora): string
    {
        try {
            $horaFormatada = Carbon::parse($hora)->format('H:i:s');
            $dataFormatada = Carbon::parse($data)->format('Y-m-d');

            $ocupado = Reserva::whereDate('date', $dataFormatada)
                ->where('start_time', $horaFormatada)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($ocupado) {
                return "O horário das {$hora} no dia {$data} está OCUPADO. Informe o cliente e peça para escolher outro horário.";
            }

            return "O horário das {$hora} no dia {$data} está LIVRE. Siga para o Passo 4 (perguntar forma de pagamento e valor do sinal). NÃO gere o PIX ainda.";
        } catch (\Exception $e) {
            Log::error("Erro na verificação de disponibilidade: " . $e->getMessage());
            return "Ocorreu um erro no sistema. Peça para o cliente aguardar um momento.";
        }
    }
}