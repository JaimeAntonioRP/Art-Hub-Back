<?php

namespace App\Filament\Resources\Artists;

use App\Models\Artist;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Acciones de revisión del perfil (§6). La regla de estados vive en Artist::transitionTo(). */
final class ReviewActions
{
    /** @return array<Action> */
    public static function all(): array
    {
        return [
            self::make('approve', Artist::STATUS_APROBADO, 'Aprobar', 'success', Heroicon::OutlinedCheckCircle)
                ->label(fn (Artist $record) => $record->status === Artist::STATUS_INACTIVO ? 'Reactivar' : 'Aprobar')
                ->requiresConfirmation(),
            self::make('observe', Artist::STATUS_OBSERVADO, 'Observar', 'warning', Heroicon::OutlinedChatBubbleLeftEllipsis)
                ->schema([self::noteField('Qué debe corregir el artista')]),
            self::make('reject', Artist::STATUS_RECHAZADO, 'Rechazar', 'danger', Heroicon::OutlinedXCircle)
                ->schema([self::noteField('Motivo del rechazo')]),
            self::make('deactivate', Artist::STATUS_INACTIVO, 'Desactivar', 'gray', Heroicon::OutlinedPauseCircle)
                ->requiresConfirmation()
                ->modalDescription('El perfil y sus obras dejan de verse en el sitio público.'),
        ];
    }

    private static function make(string $name, string $status, string $label, string $color, Heroicon $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->icon($icon)
            ->visible(fn (Artist $record) => $record->canTransitionTo($status))
            ->action(function (Artist $record, array $data) use ($status, $label) {
                $record->transitionTo($status, auth()->user(), $data['note'] ?? null);

                Notification::make()
                    ->title("{$label}: {$record->name}")
                    ->body($record->user ? 'Se avisó al artista por correo.' : null)
                    ->success()
                    ->send();
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
