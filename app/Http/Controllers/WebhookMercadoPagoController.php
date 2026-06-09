<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Reserva;
use App\Services\WhatsAppService;

class WebhookMercadoPagoController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info("Webhook do Mercado Pago recebido na Arena: " . json_encode($payload));

        // O Mercado Pago avisa sobre "payments" enviando o tipo e o ID da transação
        if (isset($payload['type']) && $payload['type'] === 'payment') {
            $paymentId = $payload['data']['id'] ?? null;

            if ($paymentId) {
                // Consultamos o status real do pagamento direto no servidor seguro do Mercado Pago
                $token = env('MERCADO_PAGO_ACCESS_TOKEN');
                $response = Http::withToken($token)->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

                if ($response->successful()) {
                    $paymentData = $response->json();
                    $status = $paymentData['status'] ?? 'pending';

                    Log::info("Status verificado no Mercado Pago para o ID {$paymentId}: {$status}");

                    if ($status === 'approved') {
                        // Busca a reserva atrelada a esse pagamento
                        $reserva = Reserva::where('payment_id', $paymentId)->first();

                        if ($reserva && $reserva->status !== 'confirmed') {
                            // 🚀 Efetua a baixa automática
                            $reserva->update([
                                'status' => 'confirmed',
                                'payment_status' => 'approved'
                            ]);

                            Log::info("Reserva ID {$reserva->id} CONFIRMADA automaticamente por PIX!");

                            // Busca o telefone do cliente para parabenizá-lo
                            $user = $reserva->user;
                            if ($user && $user->telefone) {
                                $mensagemSucesso = "🎉 *Pagamento Confirmado!*\n\nOlá, {$user->name}, seu PIX de sinal foi compensado com sucesso pelo banco. Sua reserva para a quadra da *Arena Elizeu* está confirmada e garantida!";
                                
                                $this->whatsAppService->sendMessage($user->telefone, $mensagemSucesso);
                            }
                        }
                    }
                }
            }
        }

        // Retornamos obrigatoriamente HTTP 200 ou 201 para o Mercado Pago não achar que nosso servidor caiu
        return response()->json(['status' => 'success'], 200);
    }
}