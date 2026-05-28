<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\WhatsAppService;
use App\Http\Controllers\WhatsAppController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rota oficial para o Webhook da Meta (Garantindo o WhatsAppController)
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppController::class, 'handleWebhook']);

Route::get('/whatsapp/teste-envio', function(WhatsAppService $ws) {
    // Vamos testar o envio direto usando o mesmo número do log da Meta
    $resultado = $ws->sendMessage('559181490019', 'Fala Marcos! Conexão direta da VPS funcionando!');
    return response()->json(['enviado' => $resultado]);
});