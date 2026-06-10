<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Quadra Mestre", "Loja 2"
            $table->string('phone_number', 50); // Ex: 559186056902
            $table->string('phone_number_id', 50)->nullable(); // ID da Meta
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('arena_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('whatsapp_connections');
    }
};