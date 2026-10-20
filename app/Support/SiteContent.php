<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Ajustes globales del sitio público (marca, header, footer, contacto), guardados
 * en la tabla settings. Las listas se guardan como JSON.
 */
final class SiteContent
{
    /** Clave => valor por defecto. */
    public const DEFAULTS = [
        'brand_name' => 'ArtHub',
        'brand_tagline' => 'Arte · Tecnología · Confianza',
        'logo' => null,       // archivo subido en el panel (disco de imágenes)
        'logo_url' => null,   // URL o ruta del frontend (logo anterior al panel)
        'meta_description' => 'Catálogo de arte cusqueño con fichas técnicas, artistas revisados y verificación de obras.',
        'utility_text' => 'Cusco, Perú',
        'utility_link_label' => 'Verificar una obra',
        'utility_link_url' => '/verificar',
        'header_menu' => [],
        'footer_about' => null,
        'footer_columns' => [],
        'copyright' => null,
        'contact_email' => null,
        'contact_phone' => null,
        'contact_address' => null,
        'social_links' => [],
    ];

    private const LISTS = ['header_menu', 'footer_columns', 'social_links'];

    /** @return array<string, mixed> */
    public static function all(): array
    {
        $stored = Setting::map();
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $raw = $stored[$key] ?? null;
            $values[$key] = in_array($key, self::LISTS, true)
                ? (json_decode((string) $raw, true) ?: $default)
                : ($raw ?? $default);
        }

        return $values;
    }

    /** @param array<string, mixed> $values */
    public static function save(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => in_array($key, self::LISTS, true) ? json_encode(array_values($value ?? []), JSON_UNESCAPED_UNICODE) : $value],
            );
        }
    }

    /** Forma pública para el frontend (GET /api/site). */
    public static function forApi(): array
    {
        $s = self::all();

        return [
            'brand' => [
                'name' => $s['brand_name'],
                'tagline' => $s['brand_tagline'],
                'logo_url' => $s['logo'] ? Disks::mediaUrl($s['logo']) : $s['logo_url'],
            ],
            'meta_description' => $s['meta_description'],
            'utility' => [
                'text' => $s['utility_text'],
                'link_label' => $s['utility_link_label'],
                'link_url' => $s['utility_link_url'],
            ],
            'header_menu' => array_values($s['header_menu']),
            'footer' => [
                'about' => $s['footer_about'],
                'columns' => array_values($s['footer_columns']),
                'copyright' => $s['copyright'],
            ],
            'contact' => [
                'email' => $s['contact_email'],
                'phone' => $s['contact_phone'],
                'address' => $s['contact_address'],
            ],
            'social_links' => array_values($s['social_links']),
        ];
    }
}
