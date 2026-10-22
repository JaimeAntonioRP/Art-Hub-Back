<?php

namespace App\Filament\RelationManagers;

use App\Filament\Schemas\SaleFields;
use App\Models\Artwork;
use App\Models\Sale;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

/** Reservas, ventas y transferencias de la obra (§26). Solo el admin. */
class ArtworkSalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    protected static ?string $title = 'Ventas';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'registros';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function isReadOnly(): bool
    {
        return true; // se registra y se anula con acciones propias; no se edita
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['registrar', 'commercialRequest']))
            ->columns([
                TextColumn::make('date')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Sale::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Sale::TYPE_VENTA => 'success',
                        Sale::TYPE_RESERVA => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('party_name')
                    ->label('Comprador o propietario')
                    ->description(fn (Sale $record) => collect([$record->party_document, $record->party_email, $record->party_phone])->filter()->implode(' · ') ?: null)
                    ->wrap(),
                TextColumn::make('final_price')
                    ->label('Precio final')
                    ->formatStateUsing(fn ($state, Sale $record) => $state !== null ? Number::currency((float) $state, $record->currency ?? 'PEN', 'es_PE') : '—'),
                TextColumn::make('state')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(fn (Sale $record) => $record->isCancelled() ? 'Anulado' : 'Vigente')
                    ->color(fn (string $state) => $state === 'Anulado' ? 'danger' : 'gray')
                    ->description(fn (Sale $record) => $record->cancel_reason
                        ? str($record->cancel_reason)->limit(50)
                        : ($record->commercialRequest ? 'Desde la solicitud de '.$record->commercialRequest->name : null)),
                TextColumn::make('registrar.name')
                    ->label('Registrado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                SaleFields::registerAction('register', fn (): Artwork => $this->getOwnerRecord()),
            ])
            ->recordActions([
                Action::make('notes')
                    ->label('Notas')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('gray')
                    ->visible(fn (Sale $record) => filled($record->notes))
                    ->modalHeading('Notas internas')
                    ->modalContent(fn (Sale $record) => str($record->notes)->markdown()->toHtmlString())
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('cancel')
                    ->label('Anular')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (Sale $record) => ! $record->isCancelled()
                        && $this->getOwnerRecord()->sales()->whereNull('cancelled_at')->max('id') === $record->id)
                    ->modalHeading(fn (Sale $record) => 'Anular '.mb_strtolower(Sale::TYPE_LABELS[$record->type]).' del '.$record->date->format('d/m/Y'))
                    ->modalDescription('Para corregir un registro equivocado. La disponibilidad y el propietario vuelven a como estaban antes; el registro queda en la lista como anulado.')
                    ->modalSubmitActionLabel('Anular registro')
                    ->schema([
                        Textarea::make('reason')->label('Motivo')->required()->maxLength(500)->rows(3),
                    ])
                    ->action(function (Sale $record, array $data) {
                        try {
                            $record->cancel(auth()->user(), $data['reason']);
                        } catch (DomainException $e) {
                            Notification::make()->danger()->title('No se pudo anular')->body($e->getMessage())->send();

                            return;
                        }
                        Notification::make()->success()->title('Registro anulado')->send();
                    }),
            ])
            ->emptyStateHeading('Sin movimientos')
            ->emptyStateDescription('Registra aquí una reserva, una venta o un cambio de propietario. Puedes hacerlo también desde una solicitud.');
    }
}
