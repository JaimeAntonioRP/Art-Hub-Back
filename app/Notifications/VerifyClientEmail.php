<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Verificación del correo de un cliente. Hasta verificarlo no ve sus solicitudes: se
 * asocian por correo, y así nadie ve las de otra persona registrándose con su dirección.
 */
class VerifyClientEmail extends VerifyEmail
{
    protected function verificationUrl($notifiable): string
    {
        return Filament::getPanel('cliente')->getVerifyEmailUrl($notifiable);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma tu correo en ArtHub')
            ->greeting('¡Hola!')
            ->line('Confirma tu correo para ver el estado de tus solicitudes en ArtHub.')
            ->action('Confirmar mi correo', $this->verificationUrl($notifiable))
            ->line('Si no creaste una cuenta en ArtHub, ignora este mensaje.');
    }
}
