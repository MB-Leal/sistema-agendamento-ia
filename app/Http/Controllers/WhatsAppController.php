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

                    // 🛡️ TRAVA MESTRE: Consulta via coluna oficial do banco 'whatsapp_contact'
                    $usuario = null;
                    try {
                        $usuario = \App\Models\User::where('whatsapp_contact', $phoneContact)
                            ->orWhere('whatsapp_contact', 'like', '%' . substr($phoneContact, -8))
                            ->first();
                    } catch (\Exception $e) {
                        Log::error("Erro no cruzamento de dados: " . $e->getMessage());
                    }

                    // Se o utilizador estiver em atendimento Humano ativo, ignora a IA
                    if ($usuario && isset($usuario->chat_human_mode) && $usuario->chat_human_mode == 1) {
                        Log::info("Mensagem recebida mas ignorada pela IA. Usuário {$phoneContact} está em atendimento Humano.");
                        return response('EVENT_RECEIVED', 200);
                    }

                    // ⏳ 🏁 REGRA DE NEGÓCIO: SESSÃO DE 2 HORAS (EXPIRAÇÃO DE CONVERSA)
                    // Buscamos a última mensagem registrada desse cliente no banco de dados antes desta entrada
                    $ultimaMensagemDoBanco = WhatsAppMessage::where('remote_jid', $phoneContact . '@s.whatsapp.net')
                        ->orderBy('id', 'desc')
                        ->first();

                    $historicoExpirado = false;
                    if ($ultimaMensagemDoBanco) {
                        $horarioUltimaMsg = \Carbon\Carbon::parse($ultimaMensagemDoBanco->timestamp);
                        $tempoInatividadeEmMinutos = now()->diffInMinutes($horarioUltimaMsg);

                        // Se ficou mais de 120 minutos (2 horas) sem interagir, consideramos a sessão EXPIRADA
                        if ($tempoInatividadeEmMinutos >= 120) {
                            Log::info("Sessão expirada para o cliente {$phoneContact}. Tempo de inatividade: {$tempoInatividadeEmMinutos} minutos. Reiniciando fluxo da IA do zero.");
                            $historicoExpirado = true;
                        }
                    }

                    // Grava histórico de entrada do cliente no painel
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

                    // Processamento inteligente do fluxo OpenAI
                    try {
                        // Passamos o parâmetro $historicoExpirado para o seu serviço da OpenAI saber se limpa as mensagens antigas
                        $aiResponse = $this->openAIService->getAIResponse($phoneContact, $messageText, $historicoExpirado);

                        if ($aiResponse) {

                            // 🚨 GATILHO A: TRANSBORDO PARA O ATENDENTE HUMANO
                            if (str_contains($aiResponse, '[ATIVAR_HUMANO]')) {
                                $aiResponse = str_replace('[ATIVAR_HUMANO]', '', $aiResponse);
                                $aiResponse .= "\n\n_🤖 Atendimento automático pausado. Um atendente humano assumirá a conversa em breve._";

                                if ($usuario) {
                                    try {
                                        $usuario->update(['chat_human_mode' => 1]);
                                        Log::info("Usuário {$phoneContact} ativado para modo humano via webhook.");
                                    } catch (\Exception $e) {
                                        Log::error("Erro ao salvar chat_human_mode: " . $e->getMessage());
                                    }
                                }
                                Log::info("Usuário {$phoneContact} solicitou transbordo humano com sucesso.");
                            }

                            // 🚨 GATILHO B: PARCERIA / DINHEIRO PRESENCIAL (RESERVA PENDENTE DIRETA)
                            if (preg_match('/\[RESERVA_PENDENTE:([0-9.]+)\]/', $aiResponse, $matches)) {
                                $aiResponse = preg_replace('/\[RESERVA_PENDENTE:([0-9.]+)\]/', '', $aiResponse);
                                Log::info("IA aprovou criação de Reserva Pendente sem PIX para {$phoneContact}");

                                try {
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

                                    // 🛠️ CORREÇÃO: Adicionada a coluna 'data'
                                    $dataAgendamento = date('Y-m-d', strtotime('next saturday'));
                                    \App\Models\Reserva::create([
                                        'user_id' => $usuario->id,
                                        'arena_id' => 1,
                                        'date' => $dataAgendamento,          // Nome correto exigido pelo Reserva.php
                                        'start_time' => '14:00:00',          // Nome correto da coluna
                                        'end_time' => '15:00:00',            // Nome correto da coluna
                                        'price' => (float)$valorPix,         // Preço
                                        'total_paid' => 0.00,
                                        'status' => 'pending',
                                        'payment_id' => $paymentId ?? null,
                                        'payment_status' => 'pending'
                                    ]);
                                    Log::info("Reserva salva com sucesso como Pendente para validação.");
                                } catch (\Exception $e) {
                                    Log::error("Erro ao salvar reserva pendente: " . $e->getMessage());
                                }
                            }

                            // 🚨 GATILHO C: CANCELAMENTO AUTO ASSISTIDO
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
                                } catch (\Exception $e) {
                                    Log::error("Erro ao cancelar reserva: " . $e->getMessage());
                                }
                            }

                            // 🎯 GATILHO D: INTERCEPÇÃO TRADICIONAL DO PIX DINÂMICO MERCADO PAGO
                            if (preg_match('/\[GERAR_PIX:([0-9.]+)\]/', $aiResponse, $matches)) {
                                $valorPix = $matches[1];
                                $aiResponse = preg_replace('/\[GERAR_PIX:([0-9.]+)\]/', '', $aiResponse);

                                Log::info("Solicitando PIX ao Mercado Pago no valor de R$ {$valorPix}");

                                $pixData = $this->mercadoPagoService->criarPix($valorPix, "Sinal de Reserva - Arena Elizeu", $customerName, $phoneContact);

                                // Logamos o array completo para termos certeza de como o Mercado Pago está respondendo
                                Log::info("Retorno do Mercado Pago: " . json_encode($pixData));

                                // Mapeamento blindado: procura pela chave correta não importa como o SDK do Mercado Pago retorne
                                $codigoCopiaECola = $pixData['copia_e_cola'] ?? $pixData['qr_code'] ?? $pixData['pix_copia_e_cola'] ?? null;
                                $paymentId = $pixData['payment_id'] ?? $pixData['id'] ?? null;

                                if ($codigoCopiaECola) {
                                    $aiResponse .= "\n\n🔑 *Aqui está o seu PIX Copia e Cola (Valor: R$ {$valorPix}):*\n\n" . $codigoCopiaECola . "\n\n_Copie o código acima e efetue o pagamento no app do seu banco para confirmação imediata._";

                                    try {
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

                                        // 🛠️ CORREÇÃO: Adicionada a coluna 'data'
                                        $dataAgendamento = date('Y-m-d', strtotime('next saturday'));
                                        \App\Models\Reserva::create([
                                            'user_id' => $usuario->id,
                                            'arena_id' => 1,
                                            'date' => $dataAgendamento,          // Nome correto exigido pelo Reserva.php
                                            'start_time' => '14:00:00',          // Nome correto da coluna
                                            'end_time' => '15:00:00',            // Nome correto da coluna
                                            'price' => (float)$valorPix,         // Preço
                                            'total_paid' => 0.00,
                                            'status' => 'pending',
                                            'payment_id' => $paymentId ?? null,
                                            'payment_status' => 'pending'
                                        ]);
                                        Log::info("PIX gerado e Reserva Pendente salva com sucesso! Payment ID: {$paymentId}");
                                    } catch (\Exception $e) {
                                        Log::error("Erro na persistência do PIX: " . $e->getMessage());
                                    }
                                } else {
                                    // Se a chave não vier, avisa o cliente para o bot não ficar mudo
                                    Log::error("Mercado Pago retornou sucesso, mas o Laravel não encontrou a chave do copia e cola.");
                                    $aiResponse .= "\n\n⚠️ Tivemos uma instabilidade com o banco ao gerar o seu código PIX. Um atendente humano vai te ajudar em instantes.";
                                }
                            }

                            // Envia a resposta final montada para o WhatsApp do Cliente
                            if (trim($aiResponse) !== '[HUMANO_ATIVO]') {
                                $this->whatsAppService->sendMessage($phoneContact, $aiResponse);

                                try {
                                    WhatsAppMessage::create([
                                        'remote_jid' => $phoneContact . '@s.whatsapp.net',
                                        'message' => $aiResponse,
                                        'from_me' => true,
                                        'timestamp' => now()
                                    ]);
                                } catch (\Exception $e) {
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Erro fatal no fluxo interno OpenAI: " . $e->getMessage());
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}
