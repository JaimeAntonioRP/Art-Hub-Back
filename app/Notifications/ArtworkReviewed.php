<?php

namespace App\Notifications;

use App\Models\Artwork;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso al artista cuando el administrador decide sobre una obra (§10). */
class ArtworkReviewed extends Notification
{
    use Queueable;

    private const SUBJECTS = [
        'aprobado'     => 'Tu obra fue aprobada',
        'publicado'    => 'Tu obra ya está publicada',
        'observado'    => 'Tu obra tiene observaciones',
        'rechazado'    => 'Tu obra no fue aprobada',
        'despublicado' => 'Tu obra dejó de mostrarse en el catálogo',
    ];

    public function __construct(public Artwork $artwork, public ?string $note = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->artwork->status;
        $mail = (new MailMessage)
            ->subject((self::SUBJECTS[$status] ?? 'Novedades sobre tu obra').": {$this->artwork->title}")
            ->greeting("Hola, {$notifiable->name}")
            ->line("«{$this->artwork->title}» ({$this->artwork->code}) ahora está: ".Artwork::STATUS_LABELS[$status].'.');

        if ($this->note) {
            $mail->line('Comentario del equipo de ArtHub:')->line($this->note);
        }
        if ($status === Artwork::STATUS_OBSERVADO) {
            $mail->line('Corrige lo indicado y vuelve a enviarla a revisión.');
        }

        return $mail->action('Ver la obra en mi panel', url("/artista/obras/{$this->artwork->id}/edit"));
    }
}
