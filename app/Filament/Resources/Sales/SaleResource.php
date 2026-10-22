<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Artworks\ArtworkResource;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Models\Sale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

/**
 * Todas las reservas, ventas y transferencias registradas (§26). Solo consulta: los
 * registros se hacen desde la obra o desde la solicitud de origen.
 */
class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'ventas';

    protected static ?string $modelLabel = 'venta';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $pluralModelLabel = 'Ventas';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('artwork'))
            ->columns([
                TextColumn::make('date')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Sale::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Sale::TYPE_VENTA => 'success',
                        Sale::TYPE_RESERVA => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('artwork.title')
                    ->label('Obra')
                    ->description(fn (Sale $record) => $record->artwork?->code)
                    ->searchable(),
                TextColumn::make('party_name')
                    ->label('Comprador o propietario')
                    ->description(fn (Sale $record) => $record->party_email)
                    ->searchable(['party_name', 'party_email', 'party_document']),
                TextColumn::make('final_price')
                    ->label('Precio final')
                    ->formatStateUsing(fn ($state, Sale $record) => $state !== null ? Number::currency((float) $state, $record->currency ?? 'PEN', 'es_PE') : '—')
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(fn (Sale $record) => $record->isCancelled() ? 'Anulado' : 'Vigente')
                    ->color(fn (string $state) => $state === 'Anulado' ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(Sale::TYPE_LABELS),
                TernaryFilter::make('cancelled_at')
                    ->label('Estado')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Anulados')
                    ->falseLabel('Vigentes')
                    ->default(false),
            ])
            ->recordUrl(fn (Sale $record) => ArtworkResource::getUrl('edit', ['record' => $record->artwork_id]))
            ->emptyStateHeading('Todavía no hay ventas registradas')
            ->emptyStateDescription('Se registran desde la ficha de la obra (pestaña Ventas) o desde una solicitud.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
        ];
    }
}
