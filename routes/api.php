<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rota oficial para a WhatsApp Cloud API da Meta (Aceita GET e POST)
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsappController::class, 'handleWebhook']);