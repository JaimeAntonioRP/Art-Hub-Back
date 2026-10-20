<?php

namespace App\Filament\Resources\Artists\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Historial de observaciones del perfil. Solo lectura: se crean con las acciones de revisión. */
class ReviewNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'reviewNotes';

    protected static ?string $title = 'Observaciones';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('author'))
            ->columns([
                TextColumn::make('body')
                    ->label('Observación')
                    ->wrap(),
                TextColumn::make('author.name')
                    ->label('Por')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
                IconColumn::make('resolved_at')
                    ->label('Subsanada')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->resolved_at !== null),
            ])
            ->emptyStateHeading('Sin observaciones');
    }
}
