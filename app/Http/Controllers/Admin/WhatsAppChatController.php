<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsAppMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WhatsAppChatController extends Controller
{
    public function index(Request $request)
    {
        // 1. Busca os números/filiais cadastrados da empresa
        // (Caso ainda não tenha cadastrado na tabela nova, simulamos o mestre atual)
        $connections = DB::table('whatsapp_connections')->get();
        if ($connections->isEmpty()) {
            DB::table('whatsapp_connections')->insert([
                'name' => 'Quadra Principal (Mestre)',
                'phone_number' => '559186056902',
                'created_at' => now(), 'updated_at' => now()
            ]);
            $connections = DB::table('whatsapp_connections')->get();
        }

        // Conexão selecionada atual
        $selectedConnectionId = $request->input('connection_id', $connections->first()->id);

        // 2. QUERY MASTER: Lista de contatos da esquerda (Igual WhatsApp Web)
        // Prioriza: 1º Humano Ativo, 2º Mensagens Não Lidas, 3º Data da última mensagem
        $contacts = WhatsAppMessage::select('remote_jid', 'customer_name', DB::raw('MAX(timestamp) as last_message_time'))
            ->groupBy('remote_jid', 'customer_name')
            ->get()
            ->map(function($contact) {
                // Remove o @s.whatsapp.net para limpar o número do cliente
                $purePhone = str_replace('@s.whatsapp.net', '', $contact->remote_jid);
                
                // Busca se esse cliente específico está com o gatilho de [ATIVAR_HUMANO] ligado no User
                $user = User::where('whatsapp_contact', $purePhone)
                            ->orWhere('whatsapp_contact', 'like', '%' . substr($purePhone, -8))
                            ->first();
                
                $contact->is_human_mode = $user && isset($user->chat_human_mode) ? $user->chat_human_mode : false;
                
                // Pega o texto da última mensagem para mostrar embaixo do nome
                $lastMsg = WhatsAppMessage::where('remote_jid', $contact->remote_jid)->orderBy('id', 'desc')->first();
                $contact->last_message_text = $lastMsg ? $lastMsg->message : '';
                
                // Conta mensagens não lidas
                $contact->unread_count = WhatsAppMessage::where('remote_jid', $contact->remote_jid)
                    ->where('from_me', false)
                    ->where('is_read', false)
                    ->count();

                return $contact;
            });

        // Ordenação inteligente para o painel
        $contacts = $contacts->sort(function($a, $b) {
            if ($a->is_human_mode != $b->is_human_mode) {
                return $b->is_human_mode <=> $a->is_human_mode; // Humano ativo vai pro topo!
            }
            if ($a->unread_count != $b->unread_count) {
                return $b->unread_count <=> $a->unread_count; // Não lidas em segundo lugar
            }
            return $b->last_message_time <=> $a->last_message_time; // Cronológica por fim
        });

        // Se houver um chat selecionado na URL, carrega o histórico dele
        $activeChat = $request->input('chat');
        $messages = [];
        if ($activeChat) {
            // Marca as mensagens desse contato como lidas ao abrir
            WhatsAppMessage::where('remote_jid', $activeChat)->update(['is_read' => true]);
            
            $messages = WhatsAppMessage::where('remote_jid', $activeChat)
                ->orderBy('id', 'asc')
                ->get();
        }

        return view('admin.whatsapp.chat', compact('connections', 'selectedConnectionId', 'contacts', 'messages', 'activeChat'));
    }

    // Método para o gestor responder direto pelo painel e desligar o modo humano
    public function sendMessage(Request $request)
    {
        $request->validate([
            'remote_jid' => 'required',
            'message' => 'required'
        ]);

        $purePhone = str_replace('@s.whatsapp.net', '', $request->remote_jid);

        // 1. Dispara fisicamente pelo seu WhatsAppService existente
        $whatsAppService = app(\App\Services\WhatsAppService::class);
        $whatsAppService->sendMessage($purePhone, $request->message);

        // 2. Grava no histórico como enviado pelo painel
        WhatsAppMessage::create([
            'remote_jid' => $request->remote_jid,
            'message' => $request->message,
            'from_me' => true,
            'is_read' => true,
            'timestamp' => now()
        ]);

        // 3. 🎯 REGRA DE NEGÓCIO: Se o gestor respondeu humana e manualmente, desliga o modo [HUMANO_ATIVO] 
        // para que a IA possa voltar a atender futuramente se o cliente mandar nova mensagem depois!
        User::where('phone_number', $purePhone)->orWhere('telefone', $purePhone)->update(['chat_human_mode' => 0]);

        return redirect()->back()->with('success', 'Mensagem enviada!');
    }
}