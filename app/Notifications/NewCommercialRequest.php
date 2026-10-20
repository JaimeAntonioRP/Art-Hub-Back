<?php

namespace App\Notifications;

use App\Models\CommercialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Aviso al equipo de ArtHub: llegó una solicitud sobre una obra (§14). */
class NewCommercialRequest extends Notification
{
    use Queueable;

    public function __construct(public CommercialRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $r = $this->request;
        $artwork = $r->artwork;
        $type = $r->type === 'intencion_compra' ? 'Intención de compra' : 'Pedido de información';

        $mail = (new MailMessage)
            ->subject("{$type}: {$artwork->title} ({$artwork->code})")
            ->greeting('Nueva solicitud')
            ->line("{$type} sobre «{$artwork->title}» de {$artwork->artist_name}.")
            ->line("De: {$r->name} · {$r->email}".($r->phone ? " · {$r->phone}" : ''));

        if ($r->message) {
            $mail->line('Mensaje:')->line($r->message);
        }

        return $mail->action('Gestionar la solicitud', url("/admin/solicitudes/{$r->id}/edit"));
    }
}
