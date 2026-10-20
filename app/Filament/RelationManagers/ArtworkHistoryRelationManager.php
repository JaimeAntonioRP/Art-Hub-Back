<?php

namespace App\Filament\RelationManagers;

use App\Models\Artwork;
use App\Models\ArtworkEvent;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Historial operativo de la obra (§11), en orden cronológico. Solo lectura. */
class ArtworkHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    protected static ?string $title = 'Historial';

    private const TYPE_LABELS = [
        'creada' => 'Registro creado',
        'editada' => 'Ficha modificada',
        'estado_cambiado' => 'Cambio de estado',
        'disponibilidad_cambiada' => 'Cambio de disponibilidad',
    ];

    /** Nombres legibles de los campos de la ficha, para «Ficha modificada». */
    private const FIELD_LABELS = [
        'title' => 'título', 'year' => 'año', 'technique' => 'técnica', 'support' => 'soporte',
        'height_cm' => 'alto', 'width_cm' => 'ancho', 'depth_cm' => 'profundidad', 'weight_kg' => 'peso',
        'description' => 'descripción', 'price' => 'precio', 'currency' => 'moneda', 'show_price' => 'mostrar precio',
        'location' => 'ubicación', 'history_notes' => 'información histórica', 'model_3d_url' => 'modelo 3D',
        'artist_id' => 'artista',
    ];

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->modifyQueryUsing(fn ($query) => $query->with('actor'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('type')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'estado_cambiado' ? 'info' : 'gray'),
                TextColumn::make('detail')
                    ->label('Detalle')
                    ->getStateUsing(fn (ArtworkEvent $record) => self::detail($record))
                    ->wrap(),
                TextColumn::make('actor.name')
                    ->label('Por')
                    ->placeholder('Sistema'),
            ])
            ->emptyStateHeading('Sin eventos todavía');
    }

    public static function detail(ArtworkEvent $event): ?string
    {
        $p = $event->payload ?? [];

        return match ($event->type) {
            'estado_cambiado' => trim(sprintf(
                '%s → %s%s',
                Artwork::STATUS_LABELS[$p['de'] ?? ''] ?? '—',
                Artwork::STATUS_LABELS[$p['a'] ?? ''] ?? '—',
                isset($p['nota']) ? ". «{$p['nota']}»" : '',
            )),
            'disponibilidad_cambiada' => (Artwork::AVAILABILITY_LABELS[$p['de'] ?? ''] ?? '—').' → '.(Artwork::AVAILABILITY_LABELS[$p['a'] ?? ''] ?? '—'),
            'editada' => 'Cambió: '.implode(', ', array_map(fn ($f) => self::FIELD_LABELS[$f] ?? $f, $p['campos'] ?? [])),
            default => null,
        };
    }
}
