<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppCloudController; // <-- Mudou para o CloudController

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rota oficial para a WhatsApp Cloud API da Meta (Aceita GET e POST)
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppCloudController::class, 'handleWebhook']);