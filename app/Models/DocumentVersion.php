<?php

namespace App\Models;

use App\Support\Fingerprint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id', 'version', 'disk', 'path', 'original_name',
        'mime', 'size', 'sha256', 'hash_algorithm', 'reason', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'version'         => 'integer',
            'size'            => 'integer',
            'verified_at'     => 'datetime',
            'verification_ok' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Cada archivo que entra al expediente queda en el historial de la obra con su huella (§24).
        static::created(function (DocumentVersion $version) {
            $document = $version->document;
            $document->artwork?->recordEvent(
                $version->version === 1 ? 'documento_agregado' : 'documento_versionado',
                array_filter([
                    'documento' => $document->title,
                    'version'   => $version->version,
                    'motivo'    => $version->reason,
                    'huella'    => $version->sha256,
                ]),
                $version->uploaded_by,
            );
        });

        static::deleted(fn (DocumentVersion $version) => Storage::disk($version->disk)->delete($version->path));
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Comprobación de integridad (§22): vuelve a calcular la huella del archivo guardado
     * y la compara con la registrada al subirlo. Si el archivo cambió o desapareció, falla.
     */
    public function verifyIntegrity(): bool
    {
        $current = Fingerprint::ofFile($this->disk, $this->path);
        $ok = $current !== null && hash_equals($this->sha256, $current);

        $this->forceFill(['verified_at' => now(), 'verification_ok' => $ok])->saveQuietly();

        if (! $ok) {
            $this->document->artwork?->recordEvent('integridad_fallida', [
                'documento' => $this->document->title,
                'version'   => $this->version,
                'motivo'    => $current === null ? 'archivo no encontrado' : 'la huella no coincide',
            ]);
        }

        return $ok;
    }

    /**
     * Respuesta de descarga con el nombre original. En R2/S3 redirige a un enlace
     * firmado de pocos minutos (el archivo no pasa por el servidor); en un disco
     * local lo envía directamente. Cada versión guarda su disco, así los archivos
     * subidos antes de un cambio de disco siguen accesibles.
     */
    public function downloadResponse(): Response
    {
        $disk = Storage::disk($this->disk);

        if (config("filesystems.disks.{$this->disk}.driver") !== 's3') {
            return $disk->download($this->path, $this->original_name);
        }

        return redirect()->away($disk->temporaryUrl(
            $this->path,
            now()->addMinutes(config('arthub.document_link_minutes')),
            ['ResponseContentDisposition' => 'attachment; filename="'.addcslashes($this->original_name, '"\\').'"'],
        ));
    }
}
