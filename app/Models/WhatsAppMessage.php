<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WhatsAppMessage extends Model
{
    use HasFactory;

    // 💡 IMPORTANTE: Força o Laravel a usar o nome correto da tabela no MySQL
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'remote_jid', 
        'message', 
        'from_me'
    ];
}