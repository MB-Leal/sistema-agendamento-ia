<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('connection_id')->nullable()->after('id');
            $table->boolean('is_read')->default(false)->after('message');
            $table->string('customer_name')->nullable()->after('remote_jid');
        });
    }

    public function down(): void {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['connection_id', 'is_read', 'customer_name']);
        });
    }
};