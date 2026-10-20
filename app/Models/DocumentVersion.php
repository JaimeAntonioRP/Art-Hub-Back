<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id', 'version', 'disk', 'path', 'original_name',
        'mime', 'size', 'sha256', 'reason', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'size'    => 'integer',
        ];
    }

    protected static function booted(): void
    {
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
