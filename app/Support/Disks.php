<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/** Discos configurables de ArtHub (config/arthub.php). */
final class Disks
{
    public static function mediaName(): string
    {
        return config('arthub.disks.media');
    }

    public static function documentsName(): string
    {
        return config('arthub.disks.documents');
    }

    public static function media(): Filesystem
    {
        return Storage::disk(self::mediaName());
    }

    public static function documents(): Filesystem
    {
        return Storage::disk(self::documentsName());
    }

    /** URL pública de una imagen: las antiguas son URLs o rutas del sitio; las nuevas, rutas del disco. */
    public static function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_match('#^(https?://|/)#', $path) ? $path : self::media()->url($path);
    }

    /** Para los paneles: las rutas antiguas (/obras/…, /artistas/…) son archivos del frontend. */
    public static function absoluteUrl(?string $url): ?string
    {
        return $url && str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? config('app.frontend_url').$url
            : $url;
    }
}
