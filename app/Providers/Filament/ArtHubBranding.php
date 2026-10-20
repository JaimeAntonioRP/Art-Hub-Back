<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Support\Colors\Color;

/**
 * Marca común de los paneles: sobrios para trabajar. Fondo blanco, texto negro
 * y botones en gris oscuro; el color queda solo para los estados (insignias,
 * avisos y acciones de riesgo), con los tonos de la marca.
 */
final class ArtHubBranding
{
    public static function apply(Panel $panel, string $section): Panel
    {
        return $panel
            ->brandName("ArtHub · {$section}")
            ->font('Inter')
            ->darkMode(false)
            ->colors([
                'primary' => Color::Neutral,
                // Fondo de página blanco en lugar del gris claro por defecto.
                'gray'    => array_replace(Color::Neutral, [50 => '#FFFFFF']),
                'info'    => Color::hex('#3F5A74'), // azul
                'success' => Color::hex('#4F7A5E'), // queñua
                'warning' => Color::hex('#C27E2C'), // ocre
                'danger'  => Color::hex('#B8653B'), // terracota
            ])
            ->login()
            ->passwordReset()
            ->profile();
    }
}
