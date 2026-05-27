<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController; // <-- Garanta os "A"s maiúsculos aqui!

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rota oficial para o Webhook da Meta (Garantindo o WhatsAppController)
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppController::class, 'handleWebhook']);