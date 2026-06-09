<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashier extends Model
{
    use HasFactory;

    protected $fillable = [
        'arena_id',
        'date',
        'calculated_amount',
        'actual_amount',
        'difference', // Adicione aqui se tiver a coluna no banco (recomendado)
        'status',
        'user_id',    // Padronizado para user_id
        'closing_time',
        'notes',
        'reopen_reason', // Importante para o histórico de reabertura
        'reopened_at',
        'reopened_by'
    ];

    protected $casts = [
        'date' => 'date',
        'closing_time' => 'datetime',
        'reopened_at' => 'datetime',
        'calculated_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    /**
     * Se a coluna 'difference' NÃO existir no banco, mantenha este Accessor.
     * Se existir, o Laravel usará o valor do banco automaticamente.
     */
    public function getDifferenceAttribute($value)
    {
        if ($value !== null) return (float) $value;
        return (float) ($this->actual_amount - $this->calculated_amount);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function arena(): BelongsTo
    {
        return $this->belongsTo(Arena::class);
    }

    /**
     * Ajustado para usar 'user_id' conforme definido no fillable e na sua Controller
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento para saber quem reabriu o caixa
     */
    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }
}
