<?php

namespace App\Models;

use App\Support\Disks;
use App\Support\Fingerprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;
use RuntimeException;

/** Documento del expediente de una obra (§9). Sus archivos viven en DocumentVersion. */
class Document extends Model
{
    public const CATEGORIES = ['declaracion', 'certificado', 'procedencia', 'informe', 'peritaje', 'permiso', 'historico', 'otro'];

    public const CATEGORY_LABELS = [
        'declaracion' => 'Declaración del artista',
        'certificado' => 'Certificado existente',
        'procedencia' => 'Procedencia',
        'informe'     => 'Informe',
        'peritaje'    => 'Peritaje de terceros',
        'permiso'     => 'Permiso',
        'historico'   => 'Documentación histórica',
        'otro'        => 'Otro',
    ];

    public const VISIBILITIES = ['privado', 'publico'];

    /** Tipos aceptados en el expediente: PDF, XML, imágenes y documentos de oficina. */
    public const ACCEPTED_MIME_TYPES = [
        'application/pdf',
        'application/xml', 'text/xml',
        'image/jpeg', 'image/png', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public const MAX_KB = 20 * 1024;

    protected $fillable = ['artwork_id', 'category', 'title', 'description', 'visibility', 'is_relevant', 'created_by'];

    protected $attributes = [
        'visibility' => 'privado',
        'is_relevant' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_relevant' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Al borrar un documento se borran también sus archivos, y queda constancia en el historial.
        static::deleting(function (Document $document) {
            $document->artwork?->recordEvent('documento_eliminado', [
                'documento' => $document->title,
                'versiones' => $document->versions->count(),
            ]);
            $document->versions->each->delete();
        });
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany('version');
    }

    /**
     * Registra un archivo ya guardado en el disco de documentos como nueva versión (§23).
     * Las versiones anteriores no se tocan. Desde la segunda, el motivo es obligatorio.
     * La huella se calcula leyendo el archivo desde el disco, para que corresponda
     * exactamente a lo almacenado (§21).
     */
    public function addVersion(string $path, string $originalName, ?User $by = null, ?string $reason = null): DocumentVersion
    {
        $next = (int) $this->versions()->max('version') + 1;
        $reason = trim((string) $reason) ?: null;

        if ($next > 1 && $reason === null) {
            throw new InvalidArgumentException('Una nueva versión necesita un motivo.');
        }

        $disk = Disks::documentsName();
        $sha256 = Fingerprint::ofFile($disk, $path)
            ?? throw new RuntimeException("No se encontró el archivo {$path} en el disco de documentos.");

        return $this->versions()->create([
            'version'        => $next,
            'disk'           => $disk,
            'path'           => $path,
            'original_name'  => $originalName,
            'mime'           => Disks::documents()->mimeType($path) ?: null,
            'size'           => Disks::documents()->size($path),
            'sha256'         => $sha256,
            'hash_algorithm' => Fingerprint::ALGORITHM,
            'reason'         => $reason,
            'uploaded_by'    => $by?->id,
        ]);
    }
}
