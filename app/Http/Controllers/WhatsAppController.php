<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \Illuminate\Support\Facades\DB;
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

                            // 🚨 GATILHO: CANCELAR RESERVA
                            if (preg_match('/\[CANCELAR_RESERVA:(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', $aiResponse, $matches)) {
                                $dataCancelamento = $matches[1];
                                $horaCancelamento = $matches[2];

                                $aiResponse = preg_replace('/\[CANCELAR_RESERVA:(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', '', $aiResponse);

                                $reservaParaCancelar = \App\Models\Reserva::where('user_id', $usuario->id)
                                    ->whereDate('date', $dataCancelamento)
                                    ->where('start_time', $horaCancelamento . ':00')
                                    ->whereIn('status', ['pending', 'confirmed'])
                                    ->first();

                                if ($reservaParaCancelar) {
                                    $reservaParaCancelar->status = 'cancelled'; // Se no seu banco for 'canceled' (com um L), mude aqui!
                                    $reservaParaCancelar->save();
                                }
                            }

                            // 🚨 GATILHO: REAGENDAR RESERVA
                            if (preg_match('/\[REAGENDAR_RESERVA:(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2}):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', $aiResponse, $matches)) {
                                $dataAntiga = $matches[1];
                                $horaAntiga = $matches[2];
                                $novaData = $matches[3];
                                $novaHora = $matches[4];

                                $aiResponse = preg_replace('/\[REAGENDAR_RESERVA:(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2}):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', '', $aiResponse);

                                $reservaParaEditar = \App\Models\Reserva::where('user_id', $usuario->id)
                                    ->whereDate('date', $dataAntiga)
                                    ->where('start_time', $horaAntiga . ':00')
                                    ->whereIn('status', ['pending', 'confirmed'])
                                    ->first();

                                if ($reservaParaEditar) {
                                    $reservaParaEditar->date = $novaData;
                                    $reservaParaEditar->start_time = $novaHora . ':00';
                                    $reservaParaEditar->end_time = date('H:i:s', strtotime($novaHora . ' +1 hour'));
                                    $reservaParaEditar->save();
                                }
                            }

                            // 🚨 GATILHO: RESERVA PENDENTE
                            if (preg_match('/\[RESERVA_PENDENTE:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', $aiResponse, $matches)) {
                                $valorSinal = (float) $matches[1];
                                $dataAg = $matches[2];
                                $horaAg = $matches[3]; // Ex: '18:00'
                                $horaFormatada = $horaAg . ':00';

                                // Remove a tag do texto que vai para o WhatsApp
                                $aiResponse = preg_replace('/\[RESERVA_PENDENTE:([\d\.]+):(\d{4}-\d{2}-\d{2}):(\d{2}:\d{2})\]/', '', $aiResponse);

                                // 🛡️ 1. Identificar o dia da semana para buscar a configuração do gestor
                                // date('w') retorna: 0 (Domingo) até 6 (Sábado)
                                $diaSemana = date('w', strtotime($dataAg));

                                // 🛡️ 2. Buscar o preço real configurado pelo gestor para este horário específico
                                // OBS: Ajuste 'HorarioConfigurado' para o nome exato do seu Model que guarda a grade do gestor
                                $configuracaoHorario = \App\Models\HorarioConfigurado::where('dia_semana', $diaSemana)
                                    ->where('hora_inicio', $horaFormatada)
                                    ->first();

                                // Se encontrou a configuração do gestor, pega o preço real. Se por algum motivo falhar, evita o erro fatal definindo 0
                                $valorReal = $configuracaoHorario ? $configuracaoHorario->preco : 0;

                                // Se o seu sistema também define a hora_fim na configuração, use-a. 
                                // Caso contrário, mantemos o padrão de 1 hora de duração.
                                $horaFim = $configuracaoHorario ? $configuracaoHorario->hora_fim : date('H:i:s', strtotime($horaFormatada . ' +1 hour'));

                                // 🛡️ 3. Verifica se a reserva já não existe para evitar duplicidade de slot
                                $reservaExistente = \App\Models\Reserva::whereDate('data', $dataAg)
                                    ->where('hora_inicio', $horaFormatada)
                                    ->whereIn('status', ['pendente_sinal', 'confirmado'])
                                    ->first();

                                if (!$reservaExistente) {
                                    \App\Models\Reserva::create([
                                        'cliente_id' => $usuario->id,           // Garante a Foreign Key do cliente
                                        'data' => $dataAg,
                                        'hora_inicio' => $horaFormatada,
                                        'hora_fim' => $horaFim,
                                        'valor_total' => $valorReal,            // <-- Valor real que o gestor definiu!
                                        'valor_sinal' => $valorSinal,           // Registra o sinal acordado
                                        'status' => 'pendente_sinal',           // Status que o front-end vai ler
                                    ]);

                                    \Illuminate\Support\Facades\Log::info("Reserva salva via IA. Cliente: {$usuario->nome}, Valor Total: R$ {$valorReal}, Sinal: R$ {$valorSinal}");
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
                                        $aiResponse .= "\n\n⏳ _Atenção: Este código expira em 30 minutos. Após o pagamento, a confirmação é automática._\n\n🔑 *Abaixo está o seu PIX Copia e Cola* no valor de R$ {$valorPix}\n\n";
                                        //$aiResponse .= "\n\n🔑 *Aqui está o seu PIX Copia e Cola (Valor: R$ {$valorPix}):*\n\n";
                                        //$aiResponse .= "```{$codigoCopiaECola}```\n\n";
                                        //$aiResponse .= "⏳ _Atenção: Este código expira em 30 minutos. Após o pagamento, a confirmação é automática._\n\n";
                                        $pixParaEnviarSeparado = $codigoCopiaECola;

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

                                if (isset($pixParaEnviarSeparado) && !empty($pixParaEnviarSeparado)) {
                                    
                                    // Manda só a chave, limpa e solta pro cliente copiar fácil
                                    $this->whatsAppService->sendMessage($phoneContact, $pixParaEnviarSeparado);
                                    
                                    try {
                                        WhatsAppMessage::create([
                                            'remote_jid' => $phoneContact . '@s.whatsapp.net',
                                            'message' => "CHAVE PIX GERADA (Oculta no log por segurança)", // Salvamos assim no log para ficar limpo
                                            'from_me' => true,
                                            'timestamp' => now()
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error("Erro log bot (PIX): " . $e->getMessage());
                                    }
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
