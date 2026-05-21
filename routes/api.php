<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rota única e oficial do Webhook para a Evolution API
Route::post('/whatsapp/webhook', [WhatsAppController::class, 'handleWebhook']);
