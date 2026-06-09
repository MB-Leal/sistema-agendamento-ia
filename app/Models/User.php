<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'name',
        'email',
        'whatsapp_contact',
        'password',
        'role', // 'admin', 'gestor', 'colaborador', 'cliente'
        'no_show_count',
        'is_vip',
        'is_blocked',
        'customer_qualification',
        'arena_id', // Para vínculo multiquadra
    ];

    /**
     * Atributos ocultos para serialização.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversão de tipos de atributos.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_vip' => 'boolean',
        'is_blocked' => 'boolean',
        'customer_qualification' => 'string',
    ];

    // =========================================================================
    // ⚙️ ACCESSORS DE PERMISSÃO (HIERARQUIA)
    // =========================================================================

    /**
     * Verifica se é o Super Usuário (Maia e Marcos - Desenvolvedores).
     * Role: 'admin'
     */
    protected function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->role === 'admin'
        );
    }

    /**
     * Verifica se é o Gestor/Dono do estabelecimento.
     * Role: 'gestor'
     */
    protected function isGestor(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->role === 'gestor'
        );
    }

    /**
     * Verifica se é um Colaborador/Funcionário da Arena.
     * Role: 'colaborador'
     */
    protected function isColaborador(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->role === 'colaborador'
        );
    }

    /**
     * Verifica se tem acesso ao painel administrativo.
     * Inclui: Admin, Gestor e Colaborador.
     */
    protected function hasAdminAccess(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->role, ['admin', 'gestor', 'colaborador'])
        );
    }

    /**
     * Verifica se o usuário é estritamente um cliente.
     */
    protected function isClient(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->role === 'cliente'
        );
    }

    // =========================================================================
    // ⚙️ ACCESSOR/MUTATOR COMBINADO (no_show_count)
    // =========================================================================

    protected function noShowCount(): Attribute
    {
        return Attribute::make(
            set: function (int $value) {
                $qualification = 'normal';
                $isBlocked = false;

                if ($value >= 2) {
                    $qualification = 'bloqueado';
                    $isBlocked = true;
                } elseif ($value === 1) {
                    $qualification = 'faltou_antes';
                }

                return [
                    'no_show_count' => $value,
                    'customer_qualification' => $qualification,
                    'is_blocked' => $isBlocked,
                ];
            },
            get: fn(int $value) => $value,
        );
    }

    // =========================================================================
    // ✅ ACCESSORS VISUAIS E FORMATAÇÃO
    // =========================================================================

    protected function statusTag(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_blocked) {
                    return '<span class="px-2 py-1 text-xs font-bold text-white bg-red-600 rounded-full">🚫 BLACKLIST</span>';
                }
                if ($this->is_vip) {
                    return '<span class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full">⭐ VIP</span>';
                }
                if ($this->customer_qualification === 'faltou_antes') {
                    return '<span class="px-2 py-1 text-xs font-bold text-yellow-900 bg-yellow-300 rounded-full">⚠️ FALTOU ANTES</span>';
                }
                return '<span class="px-2 py-1 text-xs font-bold text-gray-900 bg-gray-300 rounded-full">NORMAL</span>';
            }
        );
    }

    protected function formattedWhatsappContact(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $contact = preg_replace('/\D/', '', $attributes['whatsapp_contact'] ?? '');
                $length = strlen($contact);

                if ($length === 11) {
                    return '(' . substr($contact, 0, 2) . ') ' . substr($contact, 2, 1) . ' ' . substr($contact, 3, 4) . '-' . substr($contact, 7, 4);
                } elseif ($length === 10) {
                    return '(' . substr($contact, 0, 2) . ') ' . substr($contact, 2, 4) . '-' . substr($contact, 6, 4);
                }
                return $attributes['whatsapp_contact'] ?? '';
            },
        );
    }

    // =========================================================================
    // 🔗 RELACIONAMENTOS
    // =========================================================================

    public function arena(): BelongsTo
    {
        return $this->belongsTo(Arena::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    // =========================================================================
    // 🚀 MÉTODOS DE FUNCIONALIDADE
    // =========================================================================

    public function requiresSignal(): bool
    {
        return !$this->is_vip;
    }

    public function canSchedule(): bool
    {
        return !$this->is_blocked;
    }

    public function isBlacklisted(): bool
    {
        return $this->is_blocked;
    }
}