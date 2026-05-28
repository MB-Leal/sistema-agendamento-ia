<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $token;
    protected string $phoneId;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = env('WHATSAPP_TOKEN');
        $this->phoneId = env('WHATSAPP_PHONE_NUMBER_ID');
        $this->apiUrl = "https://graph.facebook.com/v20.0/{$this->phoneId}/messages";
    }

    /**
     * Envia uma mensagem de texto simples para o cliente
     */
    public function sendMessage(string $to, string $message): bool
    {
        // Remove caracteres especiais do número (mantendo apenas números)
        $to = preg_replace('/[^0-9]/', '', $to);

        Log::info("Enviando mensagem via Meta para {$to}: {$message}");

        $response = Http::withToken($this->token)
            ->post($this->apiUrl, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);

        if ($response->successful()) {
            Log::info("Mensagem enviada com sucesso para {$to}");
            return true;
        }

        Log::error("Erro ao enviar mensagem para {$to}: " . $response->body());
        return false;
    }
}