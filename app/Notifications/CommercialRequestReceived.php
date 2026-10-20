<?php

namespace App\Notifications;

use App\Models\CommercialRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmación al interesado de que su solicitud llegó (§14). */
class CommercialRequestReceived extends Notification
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
        $artwork = $this->request->artwork;

        return (new MailMessage)
            ->subject("Recibimos tu solicitud sobre «{$artwork->title}»")
            ->greeting("Hola, {$this->request->name}")
            ->line("Gracias por tu interés en «{$artwork->title}» de {$artwork->artist_name} ({$artwork->code}).")
            ->line('El equipo de ArtHub te escribirá pronto para darte más información y coordinar contigo.')
            ->action('Ver la obra', config('app.frontend_url')."/obras/{$artwork->id}");
    }
}
