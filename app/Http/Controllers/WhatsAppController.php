<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use App\Services\OpenAIService;
use Carbon\Carbon;
use App\Services\MercadoPagoService;

class WhatsAppController extends Controller
{
    protected $whatsAppService;
    protected $openAIService;
    protected $mercadoPagoService;

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
                    
                    // 🛡️ BUSCA DE USUÁRIO E CRIAÇÃO CASO NÃO EXISTA
                    $usuario = null;
                    try {
                        $usuario = \App\Models\User::where('whatsapp_contact', $phoneContact)
                            ->orWhere('whatsapp_contact', 'like', '%' . substr($phoneContact, -8))
                            ->first();
                            
                        // Se não existe, já cria na hora para garantir a Foreign Key
                        if (!$usuario) {
                            $usuario = \App\Models\User::create([
                                'name' => $customerName,
                                'email' => $phoneContact . '@arena.com',
                                'whatsapp_contact' => $phoneContact,
                                'password' => bcrypt(uniqid()),
                                'role' => 'customer',
                                'arena_id' => 1
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error("Erro ao buscar/criar usuário: " . $e->getMessage());
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
                        $aiResponse = $this->openAIService->getAIResponse($phoneContact, $messageText);

                        if ($aiResponse) {
                            
                            // 🚨 GATILHO: TRANSBORDO PARA ATENDENTE HUMANO
                            if (str_contains($aiResponse, '[ATIVAR_HUMANO]')) {
                                $aiResponse = str_replace('[ATIVAR_HUMANO]', '', $aiResponse);
                                $aiResponse .= "\n\n_🤖 Atendimento automático pausado. Um atendente assumirá em breve._";
                                if ($usuario) $usuario->update(['chat_human_mode' => 1]);
                            }

                            // 🚨 GATILHO: RESERVA PENDENTE (Dinheiro ou Cartão)
                            if (preg_match('/\[RESERVA_PENDENTE:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', $aiResponse, $matches)) {
                                $valorSinal = $matches[1];
                                $dataAg = $matches[2];
                                $horaAg = $matches[3];
                                $aiResponse = preg_replace('/\[RESERVA_PENDENTE:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', '', $aiResponse);
                                

                                $precoPadrao = \Illuminate\Support\Facades\DB::table('arena_configurations')->where('arena_id', 1)->value('default_price') ?? 100.00;

                                // Checa se já existe reserva para evitar duplicação
                                $reservaExistente = \App\Models\Reserva::whereDate('date', $dataAg)
                                    ->where('start_time', $horaAg . ':00')
                                    ->whereIn('status', ['pending', 'confirmed'])
                                    ->first();

                                if (!$reservaExistente) {
                                    \App\Models\Reserva::create([
                                        'user_id' => $usuario->id,
                                        'arena_id' => 1,
                                        'client_contact' => $phoneContact,
                                        'date' => $dataAg,
                                        'start_time' => $horaAg . ':00',
                                        'end_time' => date('H:i:s', strtotime($horaAg . ' +1 hour')),
                                        'price' => (float)$precoPadrao,
                                        'status' => 'pending',
                                        'payment_status' => 'pending'
                                    ]);
                                }
                            }

                            // 🎯 GATILHO: PIX DINÂMICO
                            if (preg_match('/\[GERAR_PIX:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', $aiResponse, $matches)) {
                                $valorPix = $matches[1];
                                $dataAgendamento = $matches[2];
                                $horaAgendamento = $matches[3];
                                $aiResponse = preg_replace('/\[GERAR_PIX:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', '', $aiResponse);

                                // 🛡️ CORREÇÃO: Pega o preço padrão da quadra (Se não achar, usa 100.00 como fallback)
                                $precoPadrao = \Illuminate\Support\Facades\DB::table('arena_configurations')->where('arena_id', 1)->value('default_price') ?? 100.00;

                                // Checagem Antispam: Se já existe reserva neste horário para este cliente
                                $reservaExistente = \App\Models\Reserva::where('user_id', $usuario->id)
                                    ->whereDate('date', $dataAgendamento)
                                    ->where('start_time', $horaAgendamento . ':00')
                                    ->whereIn('status', ['pending'])
                                    ->first();

                                if ($reservaExistente && $reservaExistente->payment_id) {
                                    $aiResponse .= "\n\n⚠️ Você já possui uma reserva aguardando pagamento para este horário. Verifique a chave PIX enviada anteriormente ou fale com um atendente.";
                                } else {
                                    // Gera PIX apenas se não existir
                                    $pixData = $this->mercadoPagoService->criarPix($valorPix, "Sinal - Arena Elizeu", $customerName, $phoneContact);
                                    $codigoCopiaECola = $pixData['copia_e_cola'] ?? null;
                                    $paymentId = $pixData['payment_id'] ?? null;

                                    if ($codigoCopiaECola) {
                                        // Formatação amigável
                                        $aiResponse .= "\n\n🔑 *Aqui está o seu PIX Copia e Cola (Valor: R$ {$valorPix}):*\n\n";
                                        $aiResponse .= "```{$codigoCopiaECola}
```\n\n";
                                        $aiResponse .= "⏳ _Atenção: Este código expira em 30 minutos. Após o pagamento, a confirmação é automática._";
                                        
                                        \App\Models\Reserva::create([
                                            'user_id' => $usuario->id,
                                            'arena_id' => 1,
                                            'client_contact' => $phoneContact,
                                            'date' => $dataAgendamento,
                                            'start_time' => $horaAgendamento . ':00',
                                            'end_time' => date('H:i:s', strtotime($horaAgendamento . ' +1 hour')),
                                            'price' => (float)$precoPadrao,
                                            'status' => 'pending',
                                            'payment_id' => $paymentId,
                                            'payment_status' => 'pending'
                                        ]);
                                    } else {
                                        $aiResponse .= "\n\n⚠️ Tivemos uma instabilidade ao gerar o PIX. Um atendente vai te ajudar em instantes.";
                                    }
                                }
                            }

                            // Envio final
                            if (trim($aiResponse) !== '[HUMANO_ATIVO]' && !empty(trim($aiResponse))) {
                                $this->whatsAppService->sendMessage($phoneContact, $aiResponse);
                                
                                try {
                                    WhatsAppMessage::create([
                                        'remote_jid' => $phoneContact . '@s.whatsapp.net',
                                        'message' => $aiResponse,
                                        'from_me' => true,
                                        'timestamp' => now()
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error("Erro log bot: " . $e->getMessage());
                                }
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