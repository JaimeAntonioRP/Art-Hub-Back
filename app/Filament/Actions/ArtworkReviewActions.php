<?php

namespace App\Filament\Actions;

use App\Models\Artwork;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

/** Acciones del flujo de revisión de obras (§10). Las reglas viven en Artwork::transitionTo(). */
final class ArtworkReviewActions
{
    /** @return array<Action> */
    public static function forAdmin(): array
    {
        return [
            self::make('approve', Artwork::STATUS_APROBADO, 'Aprobar', 'Obra aprobada', 'success', Heroicon::OutlinedCheckCircle)
                ->requiresConfirmation(),
            self::make('publish', Artwork::STATUS_PUBLICADO, 'Publicar', 'Obra publicada', 'success', Heroicon::OutlinedGlobeAlt)
                ->requiresConfirmation()
                ->modalDescription('La obra aparecerá en el catálogo público.'),
            self::make('observe', Artwork::STATUS_OBSERVADO, 'Observar', 'Observación enviada al artista', 'warning', Heroicon::OutlinedChatBubbleLeftEllipsis)
                ->schema([self::noteField('Qué debe corregir el artista')]),
            self::make('reject', Artwork::STATUS_RECHAZADO, 'Rechazar', 'Obra rechazada', 'danger', Heroicon::OutlinedXCircle)
                ->schema([self::noteField('Motivo del rechazo')]),
            self::make('unpublish', Artwork::STATUS_DESPUBLICADO, 'Despublicar', 'Obra despublicada', 'gray', Heroicon::OutlinedEyeSlash)
                ->requiresConfirmation()
                ->modalDescription('La obra deja de verse en el catálogo; su ficha y su historial se conservan.'),
            self::make('backToDraft', Artwork::STATUS_BORRADOR, 'Volver a borrador', 'Obra devuelta a borrador', 'gray', Heroicon::OutlinedArrowUturnLeft)
                ->requiresConfirmation(),
        ];
    }

    public static function submitForReview(): Action
    {
        return self::make('submitForReview', Artwork::STATUS_EN_REVISION, 'Enviar a revisión', 'Obra enviada a revisión', 'primary', Heroicon::OutlinedPaperAirplane)
            ->requiresConfirmation()
            ->modalDescription('Mientras el equipo de ArtHub la revisa, no podrás editarla.');
    }

    private static function make(string $name, string $status, string $label, string $done, string $color, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->icon($icon)
            ->visible(fn (Artwork $record) => $record->canTransitionTo($status, auth()->user()))
            ->action(function (Artwork $record, array $data, Action $action) use ($status, $done) {
                try {
                    $record->transitionTo($status, auth()->user(), $data['note'] ?? null);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title('No se pudo completar')->body($e->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()->title($done)->body($record->title)->success()->send();
            });
    }

    private static function noteField(string $label): Textarea
    {
        return Textarea::make('note')
            ->label($label)
            ->helperText('El artista recibe este texto por correo y lo ve en su panel.')
            ->required()
            ->rows(4);
    }
}
