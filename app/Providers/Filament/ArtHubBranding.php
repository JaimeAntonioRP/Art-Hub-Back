<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Support\Colors\Color;

/** Marca común de los paneles: misma paleta que el sitio público (globals.css del frontend). */
final class ArtHubBranding
{
    /**
     * Oro Cusco: 100/300/500/700/900 son la escala del sitio; el resto se interpoló
     * en OKLab entre ellos (600 se oscureció para que el texto blanco de los botones
     * pase AA: 4.8:1). Color::hex() generaría un oro más saturado (croma 0.17).
     */
    private const GOLD = [
        50 => '#F9F4E5', 100 => '#F4EAD0', 200 => '#E9D4A3', 300 => '#DDBE74',
        400 => '#D4B060', 500 => '#CBA24A', 600 => '#8E6D27', 700 => '#7A5C1B',
        800 => '#6A5019', 900 => '#5A4416', 950 => '#3F3010',
    ];

    /**
     * Neutros hechos a mano: los claros son la escala piedra (modo claro) y los
     * oscuros la escala azul (fondos del modo oscuro), como .surface-dark en el sitio.
     * 400 se lee sobre navy (texto secundario oscuro) y 500 sobre blanco (texto
     * secundario claro); 300 y 600 son pasos intermedios de la misma escala piedra.
     */
    private const GRAY = [
        50 => '#F7F2EB', 100 => '#EFE6D5', 200 => '#E2D3BA', 300 => '#CDBFA6',
        400 => '#B4A88F', 500 => '#6A5F51', 600 => '#554B40',
        700 => '#3F5A74', 800 => '#22384D', 900 => '#16283A', 950 => '#0D1B2A',
    ];

    public static function apply(Panel $panel, string $section): Panel
    {
        return $panel
            ->brandName("ArtHub · {$section}")
            ->font('Inter')
            ->colors([
                'primary' => self::GOLD,
                'gray'    => self::GRAY,
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
