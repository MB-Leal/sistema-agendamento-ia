<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use App\Services\OpenAIService;
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

        // 1. VALIDAÇÃO DO WEBHOOK
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
                    
                    // 🛡️ TRAVA MESTRE: Buscamos se o usuário existe utilizando estritamente a coluna real 'whatsapp_contact'
                    $usuario = \App\Models\User::where('whatsapp_contact', $phoneContact)
                        ->orWhere('whatsapp_contact', 'like', '%' . substr($phoneContact, -8))
                        ->first();
                    
                    // Se o banco de dados tiver a flag chat_human_mode ativa, barramos a IA aqui
                    if ($usuario && isset($usuario->chat_human_mode) && $usuario->chat_human_mode == 1) {
                        Log::info("Mensagem recebida mas ignorada pela IA. Usuário {$phoneContact} está em atendimento Humano.");
                        return response('EVENT_RECEIVED', 200);
                    }

                    // Grava histórico de entrada
                    try {
                        WhatsAppMessage::create([
                            'remote_jid' => $phoneContact . '@s.whatsapp.net',
                            'message' => $messageText,
                            'from_me' => false,
                            'timestamp' => now()
                        ]);
                    } catch (\Exception $dbEx) { Log::error("Erro banco: " . $dbEx->getMessage()); }

                    // Fluxo da IA
                    try {
                        $aiResponse = $this->openAIService->getAIResponse($phoneContact, $messageText);

                        if ($aiResponse) {

                            // 🚨 GATILHO A: TRANSBORDO PARA O ATENDENTE HUMANO
                            if (str_contains($aiResponse, '[ATIVAR_HUMANO]')) {
                                $aiResponse = str_replace('[ATIVAR_HUMANO]', '', $aiResponse);
                                $aiResponse .= "\n\n_🤖 Atendimento automático pausado. Um atendente humano assumirá a conversa em breve._";
                                
                                if ($usuario) {
                                    try {
                                        $usuario->update(['chat_human_mode' => 1]);
                                        Log::info("Usuário {$phoneContact} teve o chat_human_mode ativado no banco.");
                                    } catch (\Exception $e) { Log::warning("Erro ao atualizar chat_human_mode: " . $e->getMessage()); }
                                }
                                Log::info("Usuário {$phoneContact} solicitou transbordo humano com sucesso.");
                            }

                            // 🚨 GATILHO B: PARCERIA / DINHEIRO PRESENCIAL / MAQUININHA (RESERVA PENDENTE DIRETA)
                            if (preg_match('/\[RESERVA_PENDENTE:([0-9.]+)\]/', $aiResponse, $matches)) {
                                $aiResponse = preg_replace('/\[RESERVA_PENDENTE:([0-9.]+)\]/', '', $aiResponse);
                                
                                Log::info("IA aprovou criação de Reserva Pendente sem PIX (Parceria ou Presencial) para {$phoneContact}");
                                
                                try {
                                    if (!$usuario) {
                                        // 🛡️ CORREÇÃO CIRÚRGICA: Removidas colunas fantasmas e inserida a oficial 'whatsapp_contact'
                                        $usuario = \App\Models\User::create([
                                            'name' => $customerName, 
                                            'email' => $phoneContact.'@arena.com', 
                                            'whatsapp_contact' => $phoneContact, 
                                            'password' => bcrypt(uniqid()), 
                                            'role' => 'customer', 
                                            'arena_id' => 1
                                        ]);
                                    }

                                    \App\Models\Reserva::create([
                                        'user_id' => $usuario->id, 'arena_id' => 1, 'data_reserva' => date('Y-m-d', strtotime('next saturday')), 'hora_inicio' => '14:00:00', 'hora_fim' => '15:00:00', 'total_price' => 0.00, 'status' => 'pending'
                                    ]);
                                    Log::info("Reserva salva com sucesso como Pendente para validação do Gestor.");
                                } catch (\Exception $e) { Log::error("Erro ao salvar reserva pendente: ".$e->getMessage()); }
                            }

                            // 🚨 GATILHO C: CANCELAMENTO AUTO ASSISTIDO (+24 HORAS)
                            if (str_contains($aiResponse, '[CANCELAR_RESERVA]')) {
                                $aiResponse = str_replace('[CANCELAR_RESERVA]', '', $aiResponse);
                                
                                try {
                                    if ($usuario) {
                                        $ultimaReserva = \App\Models\Reserva::where('user_id', $usuario->id)->where('status', 'confirmed')->orderBy('id', 'desc')->first();
                                        if ($ultimaReserva) {
                                            $ultimaReserva->update(['status' => 'canceled']);
                                            Log::info("Reserva ID {$ultimaReserva->id} alterada para CANCELED com sucesso.");
                                        }
                                    }
                                } catch (\Exception $e) { Log::error("Erro ao cancelar reserva: ".$e->getMessage()); }
                            }

                            // 🎯 GATILHO D: INTERCEPÇÃO TRADICIONAL DO PIX DINÂMICO
                            if (preg_match('/\[GERAR_PIX:([0-9.]+)\]/', $aiResponse, $matches)) {
                                $valorPix = $matches[1];
                                $aiResponse = preg_replace('/\[GERAR_PIX:([0-9.]+)\]/', '', $aiResponse);

                                $pixData = $this->mercadoPagoService->criarPix($valorPix, "Sinal de Reserva - Arena Elizeu", $customerName, $phoneContact);

                                if ($pixData && isset($pixData['copia_e_cola'])) {
                                    $aiResponse .= "\n\n🔑 *Aqui está o seu PIX Copia e Cola (Valor: R$ {$valorPix}):*\n\n" . $pixData['copia_e_cola'] . "\n\n_Copie o código acima e efetue o pagamento no app do seu banco para confirmação imediata._";
                                    
                                    try {
                                        if (!$usuario) {
                                            // 🛡️ CORREÇÃO CIRÚRGICA: Removidas colunas fantasmas e inserida a oficial 'whatsapp_contact'
                                            $usuario = \App\Models\User::create([
                                                'name' => $customerName, 
                                                'email' => $phoneContact.'@arena.com', 
                                                'whatsapp_contact' => $phoneContact, 
                                                'password' => bcrypt(uniqid()), 
                                                'role' => 'customer', 
                                                'arena_id' => 1
                                            ]);
                                        }

                                        \App\Models\Reserva::create([
                                            'user_id' => $usuario->id, 'arena_id' => 1, 'data_reserva' => date('Y-m-d', strtotime('next saturday')), 'hora_inicio' => '14:00:00', 'hora_fim' => '15:00:00', 'total_price' => (float)$valorPix, 'status' => 'pending', 'payment_id' => $pixData['payment_id'], 'payment_status' => 'pending'
                                        ]);
                                    } catch (\Exception $e) { Log::error("Erro na persistência: ".$e->getMessage()); }
                                }
                            }

                            // Envia ao WhatsApp do Cliente
                            if (trim($aiResponse) !== '[HUMANO_ATIVO]') {
                                $this->whatsAppService->sendMessage($phoneContact, $aiResponse);
                                
                                try {
                                    WhatsAppMessage::create([
                                        'remote_jid' => $phoneContact . '@s.whatsapp.net', 'message' => $aiResponse, 'from_me' => true, 'timestamp' => now()
                                    ]);
                                } catch (\Exception $e) {}
                            }
                        }
                    } catch (\Exception $e) { Log::error("Erro fatal: " . $e->getMessage()); }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}