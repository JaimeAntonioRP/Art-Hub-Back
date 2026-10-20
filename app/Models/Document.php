<?php

namespace App\Models;

use App\Support\Disks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        // Al borrar un documento se borran también sus archivos.
        static::deleting(function (Document $document) {
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
     * Registra un archivo ya guardado en el disco de documentos como nueva versión.
     * La huella SHA-256 se calcula leyendo el archivo desde el disco, para que
     * corresponda exactamente a lo almacenado (base de la Etapa 2, §21–§23).
     */
    public function addVersion(string $path, string $originalName, ?User $by = null, ?string $reason = null): DocumentVersion
    {
        $disk = Disks::documents();
        $stream = $disk->readStream($path);
        if ($stream === null) {
            throw new RuntimeException("No se encontró el archivo {$path} en el disco de documentos.");
        }

        $hash = hash_init('sha256');
        hash_update_stream($hash, $stream);
        fclose($stream);

        return $this->versions()->create([
            'version'       => (int) $this->versions()->max('version') + 1,
            'disk'          => Disks::documentsName(),
            'path'          => $path,
            'original_name' => $originalName,
            'mime'          => $disk->mimeType($path) ?: null,
            'size'          => $disk->size($path),
            'sha256'        => hash_final($hash),
            'reason'        => $reason,
            'uploaded_by'   => $by?->id,
        ]);
    }
}
