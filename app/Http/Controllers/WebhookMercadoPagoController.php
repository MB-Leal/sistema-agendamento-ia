<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Reserva;
use App\Services\WhatsAppService;

class WebhookMercadoPagoController extends Controller
{
    protected string $mpToken;

    public function __construct()
    {
        // Pega automaticamente o token de produção que salvamos no .env
        $this->mpToken = env('MERCADO_PAGO_ACCESS_TOKEN') ?? '';
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Webhook Mercado Pago recebido:', $payload);

        // O Mercado Pago envia notificações de vários tipos. Filtramos apenas por "payment"
        if (isset($payload['type']) && $payload['type'] === 'payment') {
            $paymentId = $payload['data']['id'] ?? null;

            if ($paymentId) {
                Log::info("Processando baixa do pagamento ID: {$paymentId}");
                $this->consultarEConfirmarPagamento($paymentId);
            }
        }

        // Retornamos 200 ou 201 obrigatoriamente para o Mercado Pago saber que recebemos com sucesso
        return response()->json(['status' => 'success'], 200);
    }

    protected function consultarEConfirmarPagamento(string $paymentId)
    {
        if (empty($this->mpToken)) {
            Log::error("Erro no Webhook: Token do Mercado Pago não configurado no .env");
            return;
        }

        try {
            // Consulta a API oficial do Mercado Pago para validar o status real da transação
            $response = Http::withToken($this->mpToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if ($response->successful()) {
                $paymentData = $response->json();
                $status = $paymentData['status'] ?? 'pending';
                
                Log::info("Status retornado pelo Mercado Pago para o ID {$paymentId}: {$status}");

                // Se o status for aprovado (dinheiro na conta da Arena), damos a baixa
                if ($status === 'approved') {
                    
                    // Busca a pré-reserva que gravamos na tabela usando o ID do pagamento
                    $reserva = Reserva::where('payment_id', $paymentId)->first();

                    if ($reserva) {
                        // Se ela já estiver confirmada, não faz nada para evitar loops
                        if ($reserva->status === 'confirmed') {
                            Log::info("Reserva ID {$reserva->id} já constava como confirmada.");
                            return;
                        }

                        // 1. Atualiza os status no banco de dados
                        $reserva->update([
                            'status' => 'confirmed',
                            'payment_status' => 'approved'
                        ]);

                        Log::info("Sucesso! Reserva ID {$reserva->id} alterada para CONFIRMED via Webhook.");

                        // 2. Dispara a mensagem de vitória via WhatsApp para o cliente
                        $this->enviarMensagemConfirmacaoWhatsApp($reserva);

                    } else {
                        Log::warning("Aviso: Recebemos o pagamento ID {$paymentId}, mas nenhuma reserva correspondente foi encontrada no banco.");
                    }
                }
            } else {
                Log::error("Falha ao consultar pagamento {$paymentId} na API do Mercado Pago: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("Erro fatal no processamento do webhook de pagamento: " . $e->getMessage());
        }
    }

    protected function enviarMensagemConfirmacaoWhatsApp(Reserva $reserva)
    {
        try {
            // Carrega o usuário dono da reserva para pegar o whatsapp_contact
            $usuario = $reserva->user;

            if ($usuario && !empty($usuario->whatsapp_contact)) {
                $whatsAppService = app(\App\Services\WhatsAppService::class);
                
                // Formata a data para ficar bonita na mensagem (Ex: 13/06/2026)
                $dataFormatada = date('d/m/Y', strtotime($reserva->date));
                
                // 🛡️ CORREÇÃO: Usando Carbon para extrair a hora com precisão absoluta
                $horaInicio = \Carbon\Carbon::parse($reserva->start_time)->format('H:i');

                $mensagemSucesso = "⚽ *PAGAMENTO CONFIRMADO!* ⚽\n\n" .
                                   "Olá, *{$usuario->name}*!\n" .
                                   "Seu pagamento do sinal foi compensado e seu horário está oficialmente *GARANTIDO* no sistema!\n\n" .
                                   "📅 *Data:* {$dataFormatada}\n" .
                                   "⏰ *Horário:* {$horaInicio}h\n" .
                                   "🏟️ *Arena:* Arena Elizeu\n\n" .
                                   "_Obrigado pela preferência, bom jogo!_";

                $whatsAppService->sendMessage($usuario->whatsapp_contact, $mensagemSucesso);
                \Illuminate\Support\Facades\Log::info("Notificação de confirmação enviada com sucesso para o WhatsApp: {$usuario->whatsapp_contact}");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao enviar notificação de sucesso no WhatsApp: " . $e->getMessage());
        }
    }
}