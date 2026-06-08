<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    protected $token;
    protected $baseUrl = 'https://api.mercadopago.com/v1';

    public function __construct()
    {
        $this->token = env('MERCADO_PAGO_ACCESS_TOKEN');
    }

    /**
     * Gera uma cobrança via PIX e retorna o Copia e Cola
     */
    public function criarPix($valor, $descricao, $clienteNome, $clienteCelular)
    {
        Log::info("Iniciando geração de PIX no Mercado Pago no valor de R$ {$valor}");

        if (!$this->token) {
            Log::error("Erro: MERCADO_PAGO_ACCESS_TOKEN não configurado no .env");
            return null;
        }

        // Criamos o payload seguindo rigidamente a API oficial do Mercado Pago
        $payload = [
            'transaction_amount' => (float) $valor,
            'description' => $descricao,
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => 'arena.elizeu.' . preg_replace('/[^0-9]/', '', $clienteCelular) . '@mail.com', // E-mail fictício obrigatório
                'first_name' => $clienteNome ?? 'Cliente WhatsApp',
                'last_name' => 'Arena'
            ]
        ];

        // Fazemos a chamada HTTP nativa com cabeçalho de autenticação Idempotente
        $response = Http::withToken($this->token)
            ->withHeaders([
                'X-Idempotency-Key' => uniqid('pix_', true) // Evita cobranças duplicadas em caso de queda de rede
            ])
            ->post("{$this->baseUrl}/payments", $payload);

        if ($response->failed()) {
            Log::error("Falha ao criar PIX no Mercado Pago. Resposta: " . $response->body());
            return null;
        }

        $data = $response->json();

        // Extraímos os dois dados mais importantes gerados pelo banco
        return [
            'payment_id'     => $data['id'] ?? null,
            'copia_e_cola'   => $data['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            'status'         => $data['status'] ?? 'pending'
        ];
    }
}