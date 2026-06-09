<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use HasFactory;

    /**
     * Define o nome da tabela que o modelo deve usar no banco de dados.
     * Corrigido para "schedules" (o nome que existe no BD).
     *
     * @var string
     */
    protected $table = 'schedules'; // ⬅️ CORREÇÃO CRÍTICA AQUI

    /**
     * Os atributos que podem ser preenchidos em massa (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'day_of_week',
        'date',
        'start_time',
        'end_time',
        'price',
        'is_active',
    ];

    /**
     * Os atributos que devem ser convertidos (casted) para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'date' => 'date',
        'price' => 'decimal:2',
    ];
}
