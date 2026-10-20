<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /** Roles del plan (§5). */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ARTISTA = 'artista';
    public const ROLE_COMPRADOR = 'comprador';
    public const ROLES = [self::ROLE_ADMIN, self::ROLE_ARTISTA, self::ROLE_COMPRADOR];

    public const ROLE_LABELS = [
        'admin'     => 'Administrador',
        'artista'   => 'Artista',
        'comprador' => 'Comprador',
    ];

    /** Cada panel de Filament y el rol que puede entrar en él. */
    private const PANEL_ROLES = [
        'admin'   => self::ROLE_ADMIN,
        'artista' => self::ROLE_ARTISTA,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role'      => self::ROLE_COMPRADOR,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Al desactivar una cuenta se cierran sus sesiones de la API.
        static::updated(function (User $user) {
            if ($user->wasChanged('is_active') && ! $user->is_active) {
                $user->tokens()->delete();
            }
        });
    }

    public function artist(): HasOne
    {
        return $this->hasOne(Artist::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && (self::PANEL_ROLES[$panel->getId()] ?? null) === $this->role;
    }
}
