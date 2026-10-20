<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** Roles del plan (§5). */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ARTISTA = 'artista';
    public const ROLE_COMPRADOR = 'comprador';
    public const ROLES = [self::ROLE_ADMIN, self::ROLE_ARTISTA, self::ROLE_COMPRADOR];

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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active'         => 'boolean',
        ];
    }

    public function artist(): HasOne
    {
        return $this->hasOne(Artist::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
