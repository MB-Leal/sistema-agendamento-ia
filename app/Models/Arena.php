<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arena extends Model
{
    use HasFactory;

    /**
     * Atributos preenchíveis em massa.
     */
    protected $fillable = ['name', 'is_active'];

    /**
     * Relacionamento com as configurações de horário.
     * Define os slots de tempo disponíveis para esta arena.
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(ArenaConfiguration::class);
    }

    /**
     * Relacionamento com os Usuários (Gestores/Funcionários).
     * 🎯 ADICIONADO: Permite listar quem trabalha nesta arena específica.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relacionamento com as Reservas.
     * 🎯 ADICIONADO: Útil para relatórios de ocupação da arena.
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}