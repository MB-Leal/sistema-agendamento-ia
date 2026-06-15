<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use App\Services\OpenAIService;
use Carbon\Carbon;
use App\Services\MercadoPagoService; // 🚀 Importado a nova Service Nativa

class WhatsAppController extends Controller
{
    protected $whatsAppService;
    protected $openAIService;
    protected $mercadoPagoService; // 🚀 Adicionado propriedade

    // O Laravel injeta os três serviços automaticamente aqui no construtor
    public function __construct(
        WhatsAppService $whatsAppService,
        OpenAIService $openAIService,
        MercadoPagoService $mercadoPagoService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->openAIService = $openAIService;
        $this->mercadoPagoService = $mercadoPagoService;
    }

    public function handleWebhook(Request $request)
    {
        if ($request->isMethod('post')) {
            Log::info('Webhook recebido da Meta! Payload bruto: ' . json_encode($request->all()));
        }

        // 1. VALIDAÇÃO DO WEBHOOK (GET)
        if ($request->isMethod('get')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $token === env('WHATSAPP_VERIFY_TOKEN')) {
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }
            return response('Token inválido', 403);
        }

        // 2. PROCESSAMENTO DE MENSAGENS (POST)
        $payload = $request->all();

        if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $messageData = $payload['entry'][0]['changes'][0]['value']['messages'][0];
            $phoneContact = $messageData['from'] ?? null;
            $messageType = $messageData['type'] ?? null;
            $customerName = $payload['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'] ?? 'Cliente';

            if ($messageType === 'text') {
                $messageText = $messageData['text']['body'] ?? null;

                if ($phoneContact && $messageText) {
                    
                    // 🛡️ BUSCA DE USUÁRIO
                    $usuario = null;
                    try {
                        $usuario = \App\Models\User::where('whatsapp_contact', $phoneContact)
                            ->orWhere('whatsapp_contact', 'like', '%' . substr($phoneContact, -8))
                            ->first();
                    } catch (\Exception $e) {
                        Log::error("Erro no cruzamento de dados: " . $e->getMessage());
                    }

                    if ($usuario && isset($usuario->chat_human_mode) && $usuario->chat_human_mode == 1) {
                        return response('EVENT_RECEIVED', 200);
                    }

                    // Grava histórico
                    try {
                        WhatsAppMessage::create([
                            'remote_jid' => $phoneContact . '@s.whatsapp.net',
                            'message' => $messageText,
                            'from_me' => false,
                            'timestamp' => now()
                        ]);
                    } catch (\Exception $dbEx) {
                        Log::error("Erro banco histórico: " . $dbEx->getMessage());
                    }

                    // Processamento OpenAI
                    try {
                        $aiResponse = $this->openAIService->getAIResponse($phoneContact, $messageText, false);

                        if ($aiResponse) {
                            // 🚨 AÇÕES DE FLUXO (TRANSBORDO, PENDENTE, CANCELAR)
                            if (str_contains($aiResponse, '[ATIVAR_HUMANO]')) {
                                $aiResponse = str_replace('[ATIVAR_HUMANO]', '', $aiResponse);
                                $aiResponse .= "\n\n_🤖 Atendimento humano ativado._";
                                if ($usuario) $usuario->update(['chat_human_mode' => 1]);
                            }

                            if (preg_match('/\[RESERVA_PENDENTE:([0-9.]+)\]/', $aiResponse, $matches)) {
                                $aiResponse = preg_replace('/\[RESERVA_PENDENTE:([0-9.]+)\]/', '', $aiResponse);
                                // ... Lógica de Reserva Pendente aqui ...
                            }

                            // 🎯 PIX DINÂMICO
                            if (preg_match('/\[GERAR_PIX:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', $aiResponse, $matches)) {
                                $valorPix = $matches[1];
                                $dataAgendamento = $matches[2];
                                $horaAgendamento = $matches[3];
                                $aiResponse = preg_replace('/\[GERAR_PIX:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', '', $aiResponse);

                                $pixData = $this->mercadoPagoService->criarPix($valorPix, "Sinal - Arena Elizeu", $customerName, $phoneContact);
                                $codigoCopiaECola = $pixData['copia_e_cola'] ?? null;
                                $paymentId = $pixData['payment_id'] ?? null;

                                if ($codigoCopiaECola) {
                                    $aiResponse .= "\n\n🔑 *PIX:* " . $codigoCopiaECola;
                                    
                                    if (!$usuario) {
                                        $usuario = \App\Models\User::create([
                                            'name' => $customerName,
                                            'whatsapp_contact' => $phoneContact,
                                            'password' => bcrypt(uniqid()),
                                            'role' => 'customer'
                                        ]);
                                    }

                                    \App\Models\Reserva::create([
                                        'user_id' => $usuario->id,
                                        'arena_id' => 1,
                                        'client_contact' => $phoneContact,
                                        'date' => $dataAgendamento,
                                        'start_time' => $horaAgendamento . ':00',
                                        'end_time' => date('H:i:s', strtotime($horaAgendamento . ' +1 hour')),
                                        'price' => (float)$valorPix,
                                        'status' => 'pending',
                                        'payment_id' => $paymentId,
                                        'payment_status' => 'pending'
                                    ]);
                                }
                            }

                            // Envio final
                            if (trim($aiResponse) !== '[HUMANO_ATIVO]') {
                                $this->whatsAppService->sendMessage($phoneContact, $aiResponse);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Erro fatal no fluxo interno: " . $e->getMessage());
                    }
                }
            }
        }
        return response('EVENT_RECEIVED', 200);
    }
}
