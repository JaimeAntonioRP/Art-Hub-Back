<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Number;

/** Aviso al equipo comercial: se registró una venta, con los datos del comprador (§26). */
class SaleRegistered extends Notification
{
    use Queueable;

    public function __construct(public Sale $sale)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->sale;
        $artwork = $s->artwork;
        $price = $s->final_price !== null ? Number::currency((float) $s->final_price, $s->currency ?? 'PEN', 'es_PE') : 'sin precio registrado';

        $mail = (new MailMessage)
            ->subject("Venta registrada: {$artwork->title} ({$artwork->code})")
            ->greeting('Venta registrada')
            ->line("«{$artwork->title}» de {$artwork->artist_name} · {$artwork->code}")
            ->line("Fecha: {$s->date->format('d/m/Y')} · Precio final: {$price}")
            ->line('Comprador: '.collect([$s->party_name, $s->party_document])->filter()->implode(' · '))
            ->line('Contacto: '.(collect([$s->party_email, $s->party_phone])->filter()->implode(' · ') ?: 'sin datos de contacto'));

        if ($s->commercialRequest) {
            $mail->line("Viene de la solicitud de {$s->commercialRequest->name} ({$s->commercialRequest->email}).");
        }
        if ($s->notes) {
            $mail->line('Notas internas:')->line($s->notes);
        }
        if ($s->registrar) {
            $mail->line("Registrada por {$s->registrar->name}.");
        }

        return $mail->action('Ver la obra en el panel', url("/admin/obras/{$artwork->id}/edit"));
    }
}
