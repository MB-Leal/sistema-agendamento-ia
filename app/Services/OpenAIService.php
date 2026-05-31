<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppMessage;

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
     * Gera a resposta da IA baseada na mensagem atual e no histórico do banco local
     */
    public function getAIResponse(string $phoneContact, string $customerMessage): string
    {
        if (empty($this->apiKey)) {
            Log::error("OpenAI API Key não configurada no arquivo .env");
            return "Desculpe, nosso sistema está passando por uma manutenção rápida.";
        }

        // 1. PROMPT MESTRE: Regras de negócio estritas para a Arena Elizeu
        $systemPrompt = "Você é a assistente virtual inteligente e atendente oficial da Arena Elizeu. " .
                        "Seu objetivo é ser extremamente educada, prestativa e rápida para agendar a nossa quadra. " .
                        "Informações cruciais sobre a Arena Elizeu que você DEVE seguir:\n" .
                        "1. A Arena Elizeu possui APENAS UMA quadra de futebol soccer (society). Se o cliente pedir para agendar, você já sabe que é para essa quadra de futebol. Nunca pergunte qual o tipo de quadra ou modalidade.\n" .
                        "2. Localização: Se o cliente perguntar onde fica ou pedir a localização, envie exatamente este link do Google Maps: https://maps.app.goo.gl/mEkWThR4gkot25RD6 \n" .
                        "3. Regra de Pagamento (Sinal): Explique de forma muito educada que, para garantir e confirmar a reserva do horário no sistema, é necessário realizar o pagamento de uma parte do valor como garantia (um sinal/adiantamento). Isso evita que o dono da arena tome prejuízos com cancelamentos.\n" .
                        "Regras de Conversação:\n" .
                        "- Escreva mensagens curtas e diretas (máximo 3 frases por resposta), ideais para o WhatsApp.\n" .
                        "- Sempre pergunte o nome do cliente se ainda não souber.\n" .
                        "- Para iniciar a verificação de um agendamento, você precisa saber apenas: Nome do cliente, o Dia desejado e o Horário.\n" .
                        "Hoje é dia " . date('d/m/Y') . ".";

        // 2. RECUPERAÇÃO DA MEMÓRIA: Busca as últimas 6 mensagens trocadas com este cliente no banco de dados
        $historyMessages = [];
        try {
            $rawLogs = WhatsAppMessage::where('remote_jid', $phoneContact . '@s.whatsapp.net')
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get()
                ->reverse(); // Reverte para colocar na ordem cronológica correta (da mais antiga para a mais nova)

            foreach ($rawLogs as $log) {
                $role = $log->from_me ? 'assistant' : 'user';
                $historyMessages[] = ['role' => $role, 'content' => $log->message];
            }
        } catch (\Exception $e) {
            Log::error("Erro ao recuperar histórico do banco para OpenAI: " . $e->getMessage());
        }

        // 3. MONTAGEM DA ESTRUTURA DE COGNÇÃO (System Prompt + Histórico + Mensagem Atual)
        $messagesPayload = [];
        $messagesPayload[] = ['role' => 'system', 'content' => $systemPrompt];

        // Se houver histórico no banco, adiciona no meio do payload
        foreach ($historyMessages as $pastMessage) {
            $messagesPayload[] = $pastMessage;
        }

        // Se o histórico estiver vazio ou não pegou a mensagem atual, garante que a última mensagem do cliente feche o array
        // (Evita duplicar se o controller acabou de salvar no banco e já lemos acima)
        $lastSaved = end($historyMessages);
        if (!$lastSaved || $lastSaved['content'] !== $customerMessage) {
            $messagesPayload[] = ['role' => 'user', 'content' => $customerMessage];
        }

        Log::info("Chamando OpenAI para o cliente {$phoneContact} com histórico de " . count($historyMessages) . " mensagens.");

        // 4. DISPARO PARA A API DA OPENAI
        $response = Http::withToken($this->apiKey)
            ->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => $messagesPayload,
                'temperature' => 0.5 // Menor temperatura deixa a IA mais focada nas regras de negócio da Arena
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