<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Gera a resposta da IA baseada na mensagem do cliente
     */
    public function getAIResponse(string $phoneContact, string $customerMessage): string
    {
        if (empty($this->apiKey)) {
            Log::error("OpenAI API Key não configurada no arquivo .env");
            return "Desculpe, nosso sistema está passando por uma manutenção rápida.";
        }

        // Prompt mestre ditando o comportamento da IA para a sua Arena
        $systemPrompt = "Você é a assistente virtual inteligente da Arena Esportiva. " .
                        "Seu objetivo é ser extremamente educada, rápida e ajudar o cliente a agendar quadras. " .
                        "Responda de forma concisa e natural. Hoje é dia " . date('d/m/Y') . ".";

        Log::info("Chamando OpenAI para o cliente {$phoneContact}...");

        $response = Http::withToken($this->apiKey)
            ->post($this->apiUrl, [
                'model' => 'gpt-4o-mini', // Modelo ultra rápido e econômico ideal para WhatsApp
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $customerMessage]
                ],
                'temperature' => 0.7
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