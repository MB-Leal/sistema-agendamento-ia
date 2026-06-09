<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArenaConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'arena_id',
        'day_of_week', // 0 (Domingo) - 6 (Sábado)
        'config_data',  // NOVO: Armazenará um array JSON de faixas de preço (slots)
        'default_price', // Mantido por consistência, mas o valor real virá do config_data
        'is_active',   // Ativo/Inativo
    ];

    /**
     * O campo 'config_data' deve ser tratado como um array/objeto PHP,
     * serializado automaticamente para JSON ao salvar no DB.
     */
    protected $casts = [
        'config_data' => 'array',
    ];

    /**
     * Mapeamento dos nomes dos dias para exibição
     */
    public const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    public function getDayNameAttribute()
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Desconhecido';
    }
}
