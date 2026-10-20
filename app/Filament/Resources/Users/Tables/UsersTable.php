<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => User::ROLE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        User::ROLE_ADMIN => 'primary',
                        User::ROLE_ARTISTA => 'info',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Activa')
                    ->disabled(fn (User $record) => $record->is(auth()->user())),
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(User::ROLE_LABELS),
                TernaryFilter::make('is_active')
                    ->label('Cuenta')
                    ->trueLabel('Activas')
                    ->falseLabel('Desactivadas'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
