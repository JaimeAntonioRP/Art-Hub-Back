<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Rol')
                    ->options(User::ROLE_LABELS)
                    ->required()
                    ->default(User::ROLE_COMPRADOR)
                    // Nadie se quita a sí mismo el rol de administrador.
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                Toggle::make('is_active')
                    ->label('Cuenta activa')
                    ->default(true)
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(8))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? 'Déjala vacía para mantener la actual.'
                        : null),
            ]);
    }
}
