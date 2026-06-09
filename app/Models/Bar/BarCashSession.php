<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BarCashSession extends Model
{
    protected $table = 'bar_cash_sessions';
    
    // 1. Adicionamos 'expected_balance' (o que o sistema calcula) 
    // e 'notes' (para observações no fechamento)
    protected $fillable = [
        'user_id', 
        'opening_balance', 
        'expected_balance',
        'closing_balance', 
        'status', 
        'opened_at', 
        'closed_at',
        'notes'
    ];

    // 2. Importante: Dizemos ao Laravel que estes campos são DATAS
    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 3. RELACIONAMENTO COM MOVIMENTAÇÕES
     * Cada sessão de caixa terá vários registros de entrada/saída
     */
    public function movements()
    {
        return $this->hasMany(BarCashMovement::class, 'bar_cash_session_id');
    }
}