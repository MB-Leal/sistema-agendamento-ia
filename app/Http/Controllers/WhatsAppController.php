<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappMessage; // <-- Corrigido para 'a' minúsculo, combinando com o Model

class WhatsAppController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // 1. VALIDAÇÃO DO WEBHOOK (Exigido pela Meta - Método GET)
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

        // 2. RECEPÇÃO DE MENSAGENS REAL (Método POST)
        $payload = $request->all();
        Log::info('Webhook Meta WhatsApp Recebido:', $payload);

        // Estrutura de leitura de mensagens da API Oficial da Meta
        if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $messageData = $payload['entry'][0]['changes'][0]['value']['messages'][0];
            
            $phoneContact = $messageData['from'] ?? null; // Número do cliente
            $messageType = $messageData['type'] ?? null;

            // Captura o texto se for uma mensagem do tipo texto
            $messageText = null;
            if ($messageType === 'text') {
                $messageText = $messageData['text']['body'] ?? null;
            }

            if ($phoneContact && $messageText) {
                // Salva a mensagem no banco local usando o nome correto do Model
                WhatsappMessage::create([
                    'remote_jid' => $phoneContact . '@s.whatsapp.net', 
                    'message' => $messageText,
                    'from_me' => false 
                ]);

                Log::info("Mensagem da Meta salva do cliente {$phoneContact}: {$messageText}");
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}