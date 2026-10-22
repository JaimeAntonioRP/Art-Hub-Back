<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Huella digital de un archivo (§21). Se calcula leyendo el archivo desde su disco
 * por partes, así sirve igual para archivos grandes y para discos remotos (R2).
 */
class Fingerprint
{
    public const ALGORITHM = 'sha256';

    /** Huella del archivo, o null si ya no existe en el disco. */
    public static function ofFile(string $disk, string $path): ?string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if ($stream === null) {
            return null;
        }

        $hash = hash_init(self::ALGORITHM);
        hash_update_stream($hash, $stream);
        fclose($stream);

        return hash_final($hash);
    }
}
