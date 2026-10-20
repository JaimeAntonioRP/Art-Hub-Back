<?php

namespace App\Notifications;

use App\Models\Artist;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso al artista cuando el administrador revisa su perfil (§6). */
class ArtistProfileReviewed extends Notification
{
    use Queueable;

    private const SUBJECTS = [
        'aprobado'  => 'Tu perfil de artista fue aprobado',
        'observado' => 'Tu perfil de artista tiene observaciones',
        'rechazado' => 'Tu perfil de artista no fue aprobado',
        'inactivo'  => 'Tu perfil de artista fue desactivado',
    ];

    public function __construct(public Artist $artist, public ?string $note = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->artist->status;
        $mail = (new MailMessage)
            ->subject(self::SUBJECTS[$status] ?? 'Novedades sobre tu perfil de artista')
            ->greeting("Hola, {$notifiable->name}")
            ->line('El estado de tu perfil en ArtHub ahora es: '.Artist::STATUS_LABELS[$status].'.');

        if ($this->note) {
            $mail->line('Comentario del equipo de ArtHub:')->line($this->note);
        }
        if ($status === Artist::STATUS_OBSERVADO) {
            $mail->line('Corrige lo indicado y vuelve a enviar tu perfil a revisión.');
        }

        return $mail->action('Ir a mi panel', url('/artista'));
    }
}
