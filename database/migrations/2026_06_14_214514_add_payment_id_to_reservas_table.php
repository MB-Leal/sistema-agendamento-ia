<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->after('payment_status');
            $table->string('client_name')->nullable()->change(); // Torna o client_name opcional
        });
    }

    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('payment_id');
            $table->string('client_name')->nullable(false)->change();
        });
    }
};