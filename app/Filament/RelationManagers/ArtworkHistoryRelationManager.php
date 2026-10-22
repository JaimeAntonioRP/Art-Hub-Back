<?php

namespace App\Filament\RelationManagers;

use App\Models\Artwork;
use App\Models\ArtworkEvent;
use App\Models\CommercialRequest;
use App\Models\Sale;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
        'certificado_emitido' => 'Certificado emitido',
        'certificado_reemplazado' => 'Certificado reemplazado',
        'certificado_anulado' => 'Certificado anulado',
        'reserva_registrada' => 'Reserva registrada',
        'venta_registrada' => 'Venta registrada',
        'transferencia_registrada' => 'Transferencia registrada',
        'registro_anulado' => 'Registro de venta anulado',
    ];

    /** Grupos para filtrar el historial. */
    private const TYPE_GROUPS = [
        'ficha' => ['creada', 'editada', 'estado_cambiado', 'disponibilidad_cambiada'],
        'expediente' => ['documento_agregado', 'documento_versionado', 'documento_eliminado', 'integridad_fallida'],
        'solicitudes' => ['solicitud_recibida', 'solicitud_actualizada'],
        'certificados' => ['certificado_emitido', 'certificado_reemplazado', 'certificado_anulado'],
        'ventas' => ['reserva_registrada', 'venta_registrada', 'transferencia_registrada', 'registro_anulado'],
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
                        'integridad_fallida', 'certificado_anulado' => 'danger',
                        'certificado_emitido', 'venta_registrada' => 'success',
                        'certificado_emitido' => ($p['numero'] ?? '—').(isset($p['huella_pdf']) ? '. Huella del PDF '.substr($p['huella_pdf'], 0, 12).'…' : ''),
            'certificado_reemplazado' => sprintf('%s, reemplazado por %s', $p['numero'] ?? '—', $p['por'] ?? '—'),
            'certificado_anulado' => sprintf('%s. Motivo: %s', $p['numero'] ?? '—', $p['motivo'] ?? '—'),
            'reserva_registrada', 'venta_registrada', 'transferencia_registrada' => isset($p['fecha'])
                ? 'Con fecha '.Carbon::parse($p['fecha'])->format('d/m/Y')
                : null,
            'registro_anulado' => sprintf(
                '%s del %s. Motivo: %s',
                Sale::TYPE_LABELS[$p['tipo'] ?? ''] ?? 'Registro',
                isset($p['fecha']) ? Carbon::parse($p['fecha'])->format('d/m/Y') : '—',
                $p['motivo'] ?? '—',
            ),
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
                    ->options(['ficha' => 'Ficha y estados', 'expediente' => 'Expediente', 'certificados' => 'Certificados', 'solicitudes' => 'Solicitudes', 'ventas' => 'Ventas y propietarios'])
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
