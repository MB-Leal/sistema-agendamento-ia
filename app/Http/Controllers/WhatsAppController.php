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
        // LOG BRUTO CORRIGIDO: Posicionado corretamente dentro do método handleWebhook
        if ($request->isMethod('post')) {
            Log::info('Webhook recebido da Meta! Payload bruto: ' . json_encode($request->all()));
        }

        // 1. VALIDAÇÃO DO WEBHOOK (Exigido pela Meta no momento da configuração)
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

        // 2. RECEPÇÃO DE MENSAGENS E PROCESSAMENTO DA IA (POST)
        $payload = $request->all();

        if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $messageData = $payload['entry'][0]['changes'][0]['value']['messages'][0];
            
            $phoneContact = $messageData['from'] ?? null; // Ex: 559181490019
            $messageType = $messageData['type'] ?? null;
            $customerName = $payload['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'] ?? 'Cliente';

            // Tratamos apenas mensagens do tipo texto enviadas pelo cliente
            if ($messageType === 'text') {
                $messageText = $messageData['text']['body'] ?? null;

                if ($phoneContact && $messageText) {
                    Log::info("Processando mensagem do cliente: {$phoneContact} - Texto: {$messageText}");

                    // A) Salva a mensagem de entrada no banco local (Histórico) - Isolado em try/catch
                    try {
                        WhatsAppMessage::create([
                            'remote_jid' => $phoneContact . '@s.whatsapp.net',
                            'message' => $messageText,
                            'from_me' => false,
                            'timestamp' => now()
                        ]);
                    } catch (\Exception $dbEx) {
                        Log::error("Erro ao salvar mensagem de entrada no banco: " . $dbEx->getMessage());
                    }

                    // B) Fluxo Inteligente da IA e resposta ao cliente
                    try {
                        // Chama o cérebro da Inteligência Artificial (OpenAI) passando o texto
                        $aiResponse = $this->openAIService->getAIResponse($phoneContact, $messageText);

                        if ($aiResponse) {
                            
                            // 🎯 ENGENHARIA DE INTERCEPÇÃO DO PIX
                            // Procura o padrão [GERAR_PIX:VALOR] no texto gerado pela OpenAI
                            if (preg_match('/\[GERAR_PIX:([0-9.]+)\]/', $aiResponse, $matches)) {
                                $valorPix = $matches[1]; // Extrai o valor dinamicamente (Ex: 50.00)
                                
                                Log::info("IA solicitou geração de PIX no valor de R$ {$valorPix} para o cliente {$phoneContact}");

                                // Aciona a nossa service do Mercado Pago passando os dados coletados do Webhook
                                $pixData = $this->mercadoPagoService->criarPix(
                                    $valorPix, 
                                    "Sinal de Reserva - Arena Elizeu", 
                                    $customerName, 
                                    $phoneContact
                                );

                                if ($pixData && isset($pixData['copia_e_cola'])) {
                                    // Removemos a tag técnica invisível do texto para não ficar feio pro usuário
                                    $aiResponse = preg_replace('/\[GERAR_PIX:([0-9.]+)\]/', '', $aiResponse);
                                    
                                    // Monta o complemento da mensagem com instruções claras de Copia e Cola
                                    $complementoPix = "\n\n🔑 *Aqui está o seu PIX Copia e Cola:*\n\n" . $pixData['copia_e_cola'] . "\n\n_Copie o código acima e cole no aplicativo do seu banco para realizar o pagamento do sinal._";
                                    
                                    // Junta o texto simpático da IA com o código real do PIX
                                    $aiResponse .= $complementoPix;

                                    Log::info("PIX gerado com sucesso ID: " . $pixData['payment_id'] . ". Anexado ao fluxo do WhatsApp.");
                                    
                                    // TODO: Salvar o $pixData['payment_id'] atrelado à tabela de agendamentos temporários do banco para validar o webhook de baixa depois.
                                } else {
                                    // Fallback caso a API do Mercado Pago caia ou o token expire
                                    $aiResponse = preg_replace('/\[GERAR_PIX:([0-9.]+)\]/', '', $aiResponse);
                                    $aiResponse .= "\n\nDesculpe-me, tive um pequeno problema técnico ao gerar o seu código PIX agora. Por favor, tente novamente em um minuto ou solicite a chave PIX direta para um de nossos atendentes humana.";
                                }
                            }

                            // Envia fisicamente a mensagem da IA (com ou sem PIX) de volta para o WhatsApp
                            $this->whatsAppService->sendMessage($phoneContact, $aiResponse);

                            // Salva a resposta gerada pela IA no banco local como "enviada por mim"
                            try {
                                WhatsAppMessage::create([
                                    'remote_jid' => $phoneContact . '@s.whatsapp.net',
                                    'message' => $aiResponse,
                                    'from_me' => true,
                                    'timestamp' => now()
                                ]);
                            } catch (\Exception $dbEx2) {
                                Log::warning("Não salvou resposta no banco, mas enviou com sucesso: " . $dbEx2->getMessage());
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Erro fatal no processamento da OpenAI ou Envio: " . $e->getMessage());
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}