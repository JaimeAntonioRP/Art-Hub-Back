<?php

namespace App\Filament\Schemas;

use App\Models\Artwork;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/** Ficha de la obra (§7), compartida por el panel admin y el del artista. */
final class ArtworkFields
{
    /**
     * @param bool $forAdmin el admin elige el artista y el estado editorial;
     *                       el artista solo ve el estado (el flujo de revisión es de la semana 3).
     * @return array<\Filament\Schemas\Components\Component>
     */
    public static function sections(bool $forAdmin): array
    {
        return [
            Section::make('Identificación')
                ->columns(2)
                ->schema([
                    TextEntry::make('code')
                        ->label('Código ArtHub')
                        ->hiddenOn('create'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Artwork::STATUS_LABELS[$state] ?? $state)
                        ->color(fn (string $state) => self::statusColor($state))
                        ->hidden(fn (string $operation) => $forAdmin || $operation === 'create'),
                    Select::make('artist_id')
                        ->label('Artista')
                        ->relationship('artist', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->visible($forAdmin),
                    Select::make('status')
                        ->label('Estado')
                        ->options(Artwork::STATUS_LABELS)
                        ->default(Artwork::STATUS_BORRADOR)
                        ->required()
                        ->visible($forAdmin),
                    TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('year')
                        ->label('Año')
                        ->numeric()
                        ->minValue(1000)
                        ->maxValue((int) date('Y')),
                    TextInput::make('technique')
                        ->label('Técnica')
                        ->placeholder('Óleo')
                        ->maxLength(255),
                    TextInput::make('support')
                        ->label('Soporte')
                        ->placeholder('Lienzo')
                        ->maxLength(255),
                ]),
            Section::make('Medidas')
                ->description('Alto y ancho se usan para mostrar la obra a escala en realidad aumentada.')
                ->columns(4)
                ->schema([
                    self::measure('height_cm', 'Alto', 'cm'),
                    self::measure('width_cm', 'Ancho', 'cm'),
                    self::measure('depth_cm', 'Profundidad', 'cm'),
                    self::measure('weight_kg', 'Peso', 'kg'),
                ]),
            Section::make('Información comercial')
                ->columns(3)
                ->schema([
                    TextInput::make('price')
                        ->label('Precio')
                        ->numeric()
                        ->minValue(0)
                        ->required(fn (Get $get) => (bool) $get('show_price')),
                    Select::make('currency')
                        ->label('Moneda')
                        ->options(['PEN' => 'Soles (S/)', 'USD' => 'Dólares (US$)'])
                        ->default('USD')
                        ->required(),
                    Toggle::make('show_price')
                        ->label('Mostrar precio en el sitio')
                        ->helperText('Si está apagado, el sitio muestra «Consultar precio».')
                        ->default(true)
                        ->live()
                        ->inline(false),
                    Select::make('availability')
                        ->label('Disponibilidad')
                        ->options(Artwork::AVAILABILITY_LABELS)
                        ->default('disponible')
                        ->required(),
                    TextInput::make('location')
                        ->label('Ubicación')
                        ->placeholder('Taller del artista, Cusco')
                        ->maxLength(255)
                        ->columnSpan(2),
                ]),
            Section::make('Descripción e historia')
                ->schema([
                    Textarea::make('description')
                        ->label('Descripción')
                        ->rows(5),
                    Textarea::make('history_notes')
                        ->label('Información histórica')
                        ->helperText('Exposiciones, propietarios anteriores, publicaciones.')
                        ->rows(4),
                    TextInput::make('model_3d_url')
                        ->label('Modelo 3D (.glb)')
                        ->helperText('URL de un modelo 3D, si la obra lo tiene.')
                        ->maxLength(2048)
                        ->visible($forAdmin),
                ]),
        ];
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'publicado', 'aprobado' => 'success',
            'en_revision' => 'info',
            'observado' => 'warning',
            'rechazado' => 'danger',
            default => 'gray',
        };
    }

    private static function measure(string $field, string $label, string $unit): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->maxValue(99999)
            ->step(0.01)
            ->suffix($unit);
    }
}
