<?php

namespace App\Filament\Schemas;

use App\Models\Artwork;
use App\Models\CommercialRequest;
use App\Models\Sale;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/** Registro de una reserva, venta o transferencia (§26), desde la obra o desde una solicitud. */
class SaleFields
{
    /**
     * Acción «Registrar movimiento». Los tipos ofrecidos dependen de la disponibilidad de la obra.
     *
     * @param  \Closure(): Artwork  $artwork
     * @param  (\Closure(): ?CommercialRequest)|null  $request
     */
    public static function registerAction(string $name, \Closure $artwork, ?\Closure $request = null): Action
    {
        return Action::make($name)
            ->label(fn () => in_array(Sale::TYPE_VENTA, Sale::allowedTypesFor($artwork()), true)
                ? 'Registrar reserva o venta'
                : 'Registrar transferencia')
            ->icon(Heroicon::OutlinedBanknotes)
            ->visible(fn () => auth()->user()?->isAdmin() && Sale::allowedTypesFor($artwork()) !== [])
            ->modalHeading(fn () => 'Registrar movimiento de «'.$artwork()->title.'»')
            ->modalDescription('ArtHub no cobra en línea: registra aquí lo que se acordó con el cliente. La disponibilidad y el propietario se actualizan solos.')
            ->modalSubmitActionLabel('Registrar')
            ->fillForm(function () use ($artwork, $request) {
                $types = Sale::allowedTypesFor($artwork());
                $from = $request ? $request() : null;

                return [
                    'type'        => in_array(Sale::TYPE_VENTA, $types, true) && $from?->type === 'intencion_compra' ? Sale::TYPE_VENTA : $types[0],
                    'date'        => now()->toDateString(),
                    'currency'    => $artwork()->currency,
                    'final_price' => $artwork()->price,
                    'party_name'  => $from?->name,
                    'party_email' => $from?->email,
                    'party_phone' => $from?->phone,
                ];
            })
            ->schema(fn () => self::fields($artwork()))
            ->action(function (array $data, Action $action) use ($artwork, $request) {
                try {
                    $sale = Sale::register($artwork(), $data, auth()->user(), $request ? $request() : null);
                } catch (DomainException $e) {
                    Notification::make()->danger()->title('No se pudo registrar')->body($e->getMessage())->send();
                    $action->halt();

                    return;
                }

                Notification::make()->success()
                    ->title(Sale::TYPE_LABELS[$sale->type].' registrada')
                    ->body($sale->changesOwner() ? "Propietario actual: {$sale->party_name}." : 'La obra quedó reservada.')
                    ->send();
            });
    }

    public static function fields(Artwork $artwork): array
    {
        $types = array_intersect_key(Sale::TYPE_LABELS, array_flip(Sale::allowedTypesFor($artwork)));

        return [
            Grid::make(2)->schema([
                Select::make('type')
                    ->label('Tipo')
                    ->options($types)
                    ->required()
                    ->live()
                    ->helperText(fn (Get $get) => match ($get('type')) {
                        Sale::TYPE_RESERVA       => 'La obra pasa a «Reservada». El propietario no cambia.',
                        Sale::TYPE_VENTA         => 'La obra pasa a «Vendida» y el comprador queda como propietario actual.',
                        Sale::TYPE_TRANSFERENCIA => 'Cambia el propietario sin venta en ArtHub (herencia, donación, reventa privada).',
                        default                  => null,
                    }),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('party_name')
                    ->label(fn (Get $get) => $get('type') === Sale::TYPE_TRANSFERENCIA ? 'Nuevo propietario' : 'Comprador')
                    ->required()
                    ->maxLength(255),
                TextInput::make('party_document')
                    ->label('Documento (DNI, RUC o pasaporte)')
                    ->maxLength(40),
                TextInput::make('party_email')->label('Correo')->email()->maxLength(255),
                TextInput::make('party_phone')->label('Teléfono')->tel()->maxLength(40),
                TextInput::make('final_price')
                    ->label('Precio final')
                    ->numeric()
                    ->minValue(0)
                    ->required(fn (Get $get) => $get('type') === Sale::TYPE_VENTA)
                    ->visible(fn (Get $get) => $get('type') !== Sale::TYPE_TRANSFERENCIA),
                Select::make('currency')
                    ->label('Moneda')
                    ->options(array_combine(Artwork::CURRENCIES, Artwork::CURRENCIES))
                    ->required(fn (Get $get) => $get('type') !== Sale::TYPE_TRANSFERENCIA)
                    ->visible(fn (Get $get) => $get('type') !== Sale::TYPE_TRANSFERENCIA),
            ]),
            Textarea::make('notes')
                ->label('Notas internas')
                ->placeholder('Forma de pago, entrega, condiciones acordadas…')
                ->rows(3)
                ->maxLength(2000),
        ];
    }
}
