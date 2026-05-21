<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('remote_jid'); // Número do WhatsApp do Cliente
            $table->text('message');      // Conteúdo do texto enviado/recebido
            $table->boolean('from_me')->default(false); // true se fomos nós que enviamos, false se foi o cliente
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
