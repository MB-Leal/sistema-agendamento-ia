<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppMessage;

class WhatsAppController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        // Logamos o payload completo para podermos debugar o formato exato da Evolution v1.8.4
        Log::info('Webhook WhatsApp Recebido:', $payload);

        // Verifica se o evento recebido é de uma mensagem enviada/recebida
        if (isset($payload['event']) && $payload['event'] === 'messages.upsert') {
            $data = $payload['data'] ?? [];
            $key = $data['key'] ?? [];

            $remoteJid = $key['remoteJid'] ?? null;
            $fromMe = $key['fromMe'] ?? false;

            // Captura o texto da mensagem (pode vir direto ou dentro de uma message de contexto)
            $messageText = $data['message']['conversation'] ??
                ($data['message']['extendedTextMessage']['text'] ?? null);

            if ($remoteJid && $messageText) {
                // Salva a mensagem no banco local para construir o histórico (Memória da IA)
                WhatsAppMessage::create([
                    'remote_jid' => $remoteJid,
                    'message' => $messageText,
                    'from_me' => $fromMe
                ]);

                // Se a mensagem veio do cliente, futuramente dispararemos a IA aqui!
                if (!$fromMe) {
                    Log::info("Mensagem salva do cliente {$remoteJid}: {$messageText}");

                    // TODO: Chamar o OpenAIService passando o histórico
                }
            }
        }

        return response()->json(['status' => 'SUCCESS']);
    }
}
