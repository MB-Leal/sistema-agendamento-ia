<?php
// app/Models/AvailableSlot.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailableSlot extends Model
{
    use HasFactory;

    // Nome da tabela (padrão, mas bom especificar)
    protected $table = 'available_slots';

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'price',
        'is_active',
    ];

    // Garante que 'date' seja tratado como objeto Carbon
    protected $casts = [
        'date' => 'date',
    ];
}
