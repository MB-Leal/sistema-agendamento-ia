<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use App\Services\OpenAIService;

class WhatsAppController extends Controller
{
    protected $whatsAppService;
    protected $openAIService;

    // O Laravel injeta os dois serviços automaticamente aqui
    public function __construct(WhatsAppService $whatsAppService, OpenAIService $openAIService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->openAIService = $openAIService;
    }

    public function handleWebhook(Request $request)
    {
        // 1. VALIDAÇÃO DO WEBHOOK (Exigido pela Meta)
        if ($request->isMethod('get')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $token === env('WHATSAPP_VERIFY_TOKEN')) {
                Log::info('Webhook da Meta validado com sucesso na VPS!');
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response('Token de verificação inválido', 403);
        }

        // 2. RECEPÇÃO DE MENSAGENS E PROCESSAMENTO DA IA
        $payload = $request->all();

        if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $messageData = $payload['entry'][0]['changes'][0]['value']['messages'][0];
            
            $phoneContact = $messageData['from'] ?? null; // Ex: 5511999999999
            $messageType = $messageData['type'] ?? null;

            // Tratamos apenas mensagens do tipo texto enviadas pelo cliente
            if ($messageType === 'text') {
                $messageText = $messageData['text']['body'] ?? null;

                if ($phoneContact && $messageText) {
                    Log::info("Mensagem recebida do cliente {$phoneContact}: {$messageText}");

                    // A) Salva a mensagem de entrada no banco local (Histórico)
                    WhatsappMessage::create([
                        'remote_jid' => $phoneContact . '@s.whatsapp.net',
                        'message' => $messageText,
                        'from_me' => false
                    ]);

                    try {
                        // B) Chama o cérebro da Inteligência Artificial (OpenAI) passando o texto
                        $aiResponse = $this->openAIService->getAIResponse($phoneContact, $messageText);

                        if ($aiResponse) {
                            // C) Salva a resposta gerada pela IA no banco local como "enviada por mim"
                            WhatsappMessage::create([
                                'remote_jid' => $phoneContact . '@s.whatsapp.net',
                                'message' => $aiResponse,
                                'from_me' => true
                            ]);

                            // D) Envia fisicamente a mensagem da IA de volta para o WhatsApp do cliente
                            $this->whatsAppService->sendMessage($phoneContact, $aiResponse);
                        }
                    } catch (\Exception $e) {
                        Log::error("Erro no processamento da OpenAI ou Envio: " . $e->getMessage());
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}