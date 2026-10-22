<?php

namespace App\Filament\RelationManagers;

use App\Models\Artwork;
use App\Models\ArtworkEvent;
use App\Models\CommercialRequest;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Historial operativo de la obra (§11), en orden cronológico. Solo lectura. */
class ArtworkHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    protected static ?string $title = 'Historial';

    public const TYPE_LABELS = [
        'creada' => 'Registro creado',
        'editada' => 'Ficha modificada',
        'estado_cambiado' => 'Cambio de estado',
        'disponibilidad_cambiada' => 'Cambio de disponibilidad',
        'documento_agregado' => 'Documento agregado',
        'documento_versionado' => 'Nueva versión de documento',
        'documento_eliminado' => 'Documento eliminado',
        'integridad_fallida' => 'Integridad no verificada',
        'solicitud_recibida' => 'Solicitud recibida',
        'solicitud_actualizada' => 'Solicitud actualizada',
    ];

    /** Grupos para filtrar el historial. */
    private const TYPE_GROUPS = [
        'ficha' => ['creada', 'editada', 'estado_cambiado', 'disponibilidad_cambiada'],
        'expediente' => ['documento_agregado', 'documento_versionado', 'documento_eliminado', 'integridad_fallida'],
        'solicitudes' => ['solicitud_recibida', 'solicitud_actualizada'],
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
                    ->color(fn (string $state) => match ($state) {
                        'estado_cambiado' => 'info',
                        'integridad_fallida' => 'danger',
                        'solicitud_recibida' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('detail')
                    ->label('Detalle')
                    ->getStateUsing(fn (ArtworkEvent $record) => self::detail($record))
                    ->wrap(),
                TextColumn::make('actor.name')
                    ->label('Por')
                    ->placeholder('Sistema'),
            ])
            ->filters([
                SelectFilter::make('grupo')
                    ->label('Mostrar')
                    ->options(['ficha' => 'Ficha y estados', 'expediente' => 'Expediente', 'solicitudes' => 'Solicitudes'])
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereIn('type', self::TYPE_GROUPS[$data['value']] ?? [])
                        : $query),
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
            'documento_agregado', 'documento_versionado' => trim(sprintf(
                '«%s», versión %d%s%s',
                $p['documento'] ?? '—',
                $p['version'] ?? 1,
                isset($p['motivo']) ? ". Motivo: {$p['motivo']}" : '',
                isset($p['huella']) ? '. Huella '.substr($p['huella'], 0, 12).'…' : '',
            )),
            'documento_eliminado' => sprintf('«%s» (%d versiones)', $p['documento'] ?? '—', $p['versiones'] ?? 0),
            'integridad_fallida' => sprintf('«%s», versión %d: %s', $p['documento'] ?? '—', $p['version'] ?? 1, $p['motivo'] ?? ''),
            'solicitud_recibida' => CommercialRequest::TYPE_LABELS[$p['tipo'] ?? ''] ?? null,
            'solicitud_actualizada' => sprintf(
                '%s: %s → %s',
                CommercialRequest::TYPE_LABELS[$p['tipo'] ?? ''] ?? 'Solicitud',
                CommercialRequest::STATUS_LABELS[$p['de'] ?? ''] ?? '—',
                CommercialRequest::STATUS_LABELS[$p['a'] ?? ''] ?? '—',
            ),
            default => null,
        };
    }
}
