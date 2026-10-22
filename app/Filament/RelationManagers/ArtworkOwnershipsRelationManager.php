<?php

namespace App\Filament\RelationManagers;

use App\Models\Ownership;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Historial de propietarios (§25). Se arma solo con las ventas y transferencias; no se edita a mano. */
class ArtworkOwnershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'ownerships';

    protected static ?string $title = 'Propietarios';

    protected static ?string $modelLabel = 'propietario';

    protected static ?string $pluralModelLabel = 'propietarios';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->isAdmin(); // datos personales: solo el equipo de ArtHub
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('owner_name')
                    ->label('Propietario')
                    ->description(fn (Ownership $record) => collect([$record->owner_document, $record->owner_email])->filter()->implode(' · ') ?: null)
                    ->weight('medium'),
                TextColumn::make('origin')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Ownership::ORIGIN_LABELS[$state] ?? $state)
                    ->color('gray'),
                TextColumn::make('from_date')->label('Desde')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('to_date')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->badge(fn (Ownership $record) => $record->to_date === null)
                    ->color(fn (Ownership $record) => $record->to_date === null ? 'success' : null)
                    ->placeholder('Propietario actual'),
            ])
            ->emptyStateHeading('La obra sigue siendo de su artista')
            ->emptyStateDescription('El historial empieza con la primera venta o transferencia.');
    }
}
