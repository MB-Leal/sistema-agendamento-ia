<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\WhatsAppMessage;

class WhatsAppController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Recebe os dados da Evolution API
        $data = $request->all();

        // Verifica se é uma mensagem recebida
        if ($request->input('event') == 'messages.upsert') {
            $messageText = $data['data']['message']['conversation'] ?? '';
            $remoteJid = $data['data']['key']['remoteJid'];

            // 2. Salva no banco local
            WhatsAppMessage::create([
                'remote_jid' => $remoteJid,
                'message' => $messageText,
                'from_me' => false
            ]);

            // 3. TODO: Chamar o OpenAIService para processar e responder
            // Por enquanto, vamos apenas logar para teste
            \Log::info("Mensagem recebida de $remoteJid: $messageText");
        }

        return response()->json(['status' => 'success']);
    }
}
