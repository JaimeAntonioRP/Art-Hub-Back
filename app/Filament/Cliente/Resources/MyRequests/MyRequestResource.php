<?php

namespace App\Filament\Cliente\Resources\MyRequests;

use App\Filament\Cliente\Resources\MyRequests\Pages\ListMyRequests;
use App\Models\CommercialRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Las solicitudes del cliente: las que envió con su cuenta o con su correo verificado.
 * Solo lectura; nunca muestra las notas internas del equipo.
 */
class MyRequestResource extends Resource
{
    protected static ?string $model = CommercialRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $slug = 'solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Mis solicitudes';

    public static function canCreate(): bool
    {
        return false; // se envían desde la ficha de cada obra en el sitio
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with('artwork')
            ->where(fn (Builder $q) => $q
                ->where('user_id', $user?->id)
                ->when($user?->hasVerifiedEmail(), fn (Builder $q) => $q->orWhere('email', $user->email)));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Enviada')->date('d/m/Y'),
                TextColumn::make('artwork.title')
                    ->label('Obra')
                    ->description(fn (CommercialRequest $record) => $record->artwork?->artist_name),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => CommercialRequest::TYPE_LABELS[$state] ?? $state)
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => CommercialRequest::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'confirmada' => 'success',
                        'cancelada' => 'gray',
                        'nueva' => 'info',
                        default => 'warning',
                    }),
            ])
            ->recordUrl(fn (CommercialRequest $record) => config('app.frontend_url')."/obras/{$record->artwork_id}")
            ->openRecordUrlInNewTab()
            ->emptyStateHeading('Todavía no tienes solicitudes')
            ->emptyStateDescription('Cuando pidas información o quieras comprar una obra desde su ficha, verás aquí en qué estado está.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyRequests::route('/'),
        ];
    }
}
