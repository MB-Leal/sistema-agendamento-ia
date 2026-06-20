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

                        // 1. DECLARAMOS A VARIÁVEL AQUI (Isso resolve o erro Undefined Variable)
                        $horaFormatada = \Carbon\Carbon::parse($reserva->start_time)->format('H:i');

                        // 2. Extraímos a data e remontamos para calcular a regra de 24h sem dar erro de "Double date"
                        $dataOnly = \Carbon\Carbon::parse($reserva->date)->format('Y-m-d');
                        $dataCarb = \Carbon\Carbon::parse($dataOnly . ' ' . $horaFormatada . ':00');

                        $podeCancelar = \Carbon\Carbon::now()->diffInHours($dataCarb, false) >= 24;

                        // 3. Montamos a string usando a variável que criamos
                        $contextoReservas .= "- Reserva ID: {$reserva->id} | Data: " . \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') . " às {$horaFormatada}h | Status do Sistema: {$reserva->status} | ";
                        $contextoReservas .= $podeCancelar ? "CANCELAMENTO: Permitido (+24h de antecedência).\n" : "CANCELAMENTO: Proibido (Menos de 24h).\n";
                    }
                }
            }
        } catch (\Exception $dbEx) {
            Log::error("Erro no cruzamento de dados: " . $dbEx->getMessage());
        }

        // 2. PROMPT MESTRE RESTRITO E AVANÇADO (STATE MACHINE)
        $systemPrompt = "Você é a assistente virtual inteligente e atendente oficial da Arena Elizeu.\n" .
            "Você DEVE responder APENAS perguntas sobre agendamentos, horários, cancelamentos e o funcionamento da Arena Elizeu.\n\n" .
            "📌 CONTEXTO DO CLIENTE ATUAL:\n{$contextoCliente}\n{$contextoReservas}\n\n" .
            "========================================\n" .
            "🚨 FLUXO OBRIGATÓRIO DE ATENDIMENTO (Siga rigorosamente a ordem):\n" .
            "PASSO 1 (NOME): Se o STATUS do cliente for 'Novo', sua ÚNICA e PRIMEIRA ação é dar boas-vindas e perguntar o NOME dele para o cadastro. NÃO prossiga sem o nome.\n" .
            "PASSO 2 (O QUE DESEJA): Pergunte se ele deseja fazer um agendamento. Se sim, pergunte a data e horário.\n" .
            "PASSO 3 (CONSULTA): Use a ferramenta 'verificar_disponibilidade' OBRIGATORIAMENTE.\n" .
            "PASSO 4 (PRÉ-AGENDAMENTO): Se estiver LIVRE, pergunte CLARAMENTE: 'O horário está livre! Deseja fazer o pré-agendamento para segurar essa vaga?'. Espere o cliente dizer SIM.\n" .
            "PASSO 5 (FORMA DE PAGAMENTO): Após ele dizer sim, explique que para confirmar a reserva precisamos de um SINAL. Pergunte APENAS COMO ele deseja pagar: via PIX (online) ou Dinheiro/Cartão (presencial na Arena).\n" .
            "PASSO 6 (A REGRA DO SINAL - MUITA ATENÇÃO):\n" .
            "  - SE ELE ESCOLHER PIX: Pergunte qual valor ele quer dar de sinal (sugira R$ 50, mas diga que pode ser qualquer valor). Espere ele dizer o valor e só então gere a tag [GERAR_PIX].\n" .
            "  - SE ELE ESCOLHER DINHEIRO OU CARTÃO: NÃO PERGUNTE O VALOR DO SINAL. Apenas gere a tag [RESERVA_PENDENTE] imediatamente e avise que o pré-agendamento foi feito, mas ele precisa ir à Arena pagar para garantir.\n" .
            "========================================\n\n" .
            "### REGRAS DE CANCELAMENTO E REAGENDAMENTO ###\n" .
            "1. CANCELAR: Se o cliente pedir para cancelar, verifique as 'RESERVAS ENCONTRADAS ATIVAS'. Se o cancelamento for 'Permitido', confirme educadamente e gere a tag [CANCELAR_RESERVA:YYYY-MM-DD:HH:MM]. Se for 'Proibido', explique que não é possível cancelar com menos de 24h de antecedência.\n" .
            "2. REAGENDAR: Se pedir para mudar dia/hora, use 'verificar_disponibilidade' para a nova data. Se livre, confirme a mudança e gere a tag [REAGENDAR_RESERVA:DATA_ANTIGA:HORA_ANTIGA:NOVA_DATA:NOVA_HORA].\n" .
            "========================================\n\n" .
            "💲 GERAÇÃO DE TAGS (Invisíveis para o cliente, use apenas no final da mensagem de acordo com a regra acima):\n" .
            "- Tag para PIX: [GERAR_PIX:VALOR:DATA:HORA] (Ex: [GERAR_PIX:5.00:2026-06-18:08:00]). NÃO mostre chave no texto.\n" .
            "- Tag para PRESENCIAL (Dinheiro/Cartão): [RESERVA_PENDENTE:0.00:DATA:HORA] (Sempre gere com valor 0.00, ex: [RESERVA_PENDENTE:0.00:2026-06-18:08:00]).\n" .
            "- Tag Cancelar: [CANCELAR_RESERVA:DATA:HORA] (Ex: [CANCELAR_RESERVA:2026-06-18:08:00])\n" .
            "- Tag Reagendar: [REAGENDAR_RESERVA:DATA_ANTIGA:HORA_ANTIGA:NOVA_DATA:NOVA_HORA]\n\n" .
            "⚠️ ATENDIMENTO HUMANO: Se irritado ou pedir humano, use a tag [ATIVAR_HUMANO].\n\n" .
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
