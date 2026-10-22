<?php

namespace App\Models;

use App\Support\CertificatePdf;
use App\Support\Disks;
use App\Support\Fingerprint;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Certificado digital de una obra (§19–§20). Se emite desde una obra aprobada o publicada.
 * Una obra tiene a lo sumo un certificado vigente: reemitir reemplaza al anterior.
 */
class Certificate extends Model
{
    public const STATUS_VIGENTE = 'vigente';
    public const STATUS_ANULADO = 'anulado';
    public const STATUS_REEMPLAZADO = 'reemplazado';

    public const STATUS_LABELS = [
        self::STATUS_VIGENTE => 'Vigente',
        self::STATUS_ANULADO => 'Anulado',
        self::STATUS_REEMPLAZADO => 'Reemplazado',
    ];

    /** Estados de la obra desde los que se puede emitir. */
    public const ISSUABLE_ARTWORK_STATUSES = [Artwork::STATUS_APROBADO, Artwork::STATUS_PUBLICADO];

    protected $fillable = [
        'artwork_id', 'number', 'verification_token', 'status', 'snapshot', 'data_sha256',
        'pdf_disk', 'pdf_path', 'pdf_sha256', 'issued_at', 'issued_by',
        'revoked_at', 'revoked_by', 'revoke_reason', 'replaced_by_id',
    ];

    protected $hidden = ['pdf_disk', 'pdf_path'];

    protected function casts(): array
    {
        return [
            'snapshot'   => 'array',
            'issued_at'  => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    public function isVigente(): bool
    {
        return $this->status === self::STATUS_VIGENTE;
    }

    /** Enlace público de verificación: es lo que lleva el QR. */
    public function verificationUrl(): string
    {
        return config('app.frontend_url').'/verificar/'.$this->verification_token;
    }

    /**
     * Emite un certificado nuevo: guarda los datos certificados con su huella, genera el
     * PDF con QR y calcula la huella del PDF. Si había uno vigente, queda reemplazado.
     */
    public static function issueFor(Artwork $artwork, User $by): self
    {
        if (! in_array($artwork->status, self::ISSUABLE_ARTWORK_STATUSES, true)) {
            throw new DomainException('Solo se emite el certificado de una obra aprobada o publicada.');
        }

        return DB::transaction(function () use ($artwork, $by) {
            $previous = $artwork->certificates()->where('status', self::STATUS_VIGENTE)->lockForUpdate()->first();
            $sequence = $artwork->certificates()->count() + 1;
            $issuedAt = now()->startOfSecond();

            $certificate = $artwork->certificates()->create([
                'number'             => "{$artwork->code}-C{$sequence}",
                'verification_token' => (string) Str::uuid(),
                'status'             => self::STATUS_VIGENTE,
                'issued_at'          => $issuedAt,
                'issued_by'          => $by->id,
            ]);

            $snapshot = self::snapshotOf($artwork, $certificate->number, $issuedAt->toIso8601String());
            $certificate->snapshot = $snapshot;
            $certificate->data_sha256 = self::hashSnapshot($snapshot);

            // El PDF lleva la huella de los datos; la huella del PDF se calcula después y se publica en el portal.
            $disk = Disks::documentsName();
            $path = "certificados/{$artwork->id}/{$certificate->number}.pdf";
            Storage::disk($disk)->put($path, CertificatePdf::render($certificate, $artwork));

            $certificate->pdf_disk = $disk;
            $certificate->pdf_path = $path;
            $certificate->pdf_sha256 = Fingerprint::ofFile($disk, $path);
            $certificate->save();

            $artwork->recordEvent('certificado_emitido', [
                'numero'      => $certificate->number,
                'huella_pdf'  => $certificate->pdf_sha256,
            ], $by->id);

            if ($previous) {
                $previous->update(['status' => self::STATUS_REEMPLAZADO, 'replaced_by_id' => $certificate->id]);
                $artwork->recordEvent('certificado_reemplazado', [
                    'numero' => $previous->number,
                    'por'    => $certificate->number,
                ], $by->id);
            }

            return $certificate;
        });
    }

    /** Anula el certificado vigente. El motivo se muestra en el portal de verificación. */
    public function revoke(User $by, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Anular un certificado necesita un motivo.');
        }
        if (! $this->isVigente()) {
            throw new DomainException('Solo se anula un certificado vigente.');
        }

        $this->update([
            'status'        => self::STATUS_ANULADO,
            'revoked_at'    => now(),
            'revoked_by'    => $by->id,
            'revoke_reason' => $reason,
        ]);

        $this->artwork?->recordEvent('certificado_anulado', ['numero' => $this->number, 'motivo' => $reason], $by->id);
    }

    /**
     * Datos que certifica el documento, en un orden fijo para que la huella sea reproducible:
     * la ficha de la obra, la huella de la imagen principal y la de cada documento relevante.
     */
    public static function snapshotOf(Artwork $artwork, string $number, string $issuedAt): array
    {
        $artwork->loadMissing(['artist', 'images', 'documents.latestVersion']);

        return [
            'certificado' => $number,
            'emitido'     => $issuedAt,
            'obra'        => self::artworkData($artwork),
            'imagen_principal_sha256' => $artwork->images->firstWhere('is_primary', true)?->sha256,
            'documentos'  => $artwork->documents
                ->where('is_relevant', true)
                ->filter(fn (Document $d) => $d->latestVersion)
                ->sortBy('id')
                ->map(fn (Document $d) => [
                    'titulo'    => $d->title,
                    'categoria' => $d->category,
                    'version'   => $d->latestVersion->version,
                    'sha256'    => $d->latestVersion->sha256,
                ])
                ->values()
                ->all(),
        ];
    }

    private static function artworkData(Artwork $artwork): array
    {
        // 80.00 → "80", 80.50 → "80.5": la misma cifra da siempre el mismo texto.
        $number = fn ($v) => $v === null ? null : preg_replace('/\.00$|(\.\d)0$/', '$1', number_format((float) $v, 2, '.', ''));

        return [
            'codigo'      => $artwork->code,
            'titulo'      => $artwork->title,
            'artista'     => $artwork->artist?->name,
            'anio'        => $artwork->year,
            'tecnica'     => $artwork->technique,
            'soporte'     => $artwork->support,
            'alto_cm'     => $number($artwork->height_cm),
            'ancho_cm'    => $number($artwork->width_cm),
            'profundidad_cm' => $number($artwork->depth_cm),
        ];
    }

    public static function hashSnapshot(array $snapshot): string
    {
        return hash(Fingerprint::ALGORITHM, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * ¿Los datos certificados siguen siendo los de la obra? Si la ficha, la imagen principal
     * o un documento relevante cambió desde la emisión, conviene reemitir.
     */
    public function matchesCurrentArtwork(): bool
    {
        if (! $this->snapshot || ! $this->artwork) {
            return false;
        }

        $current = self::snapshotOf($this->artwork, $this->snapshot['certificado'], $this->snapshot['emitido']);

        return self::hashSnapshot($current) === $this->data_sha256;
    }

    /** El PDF emitido, con un nombre legible. */
    public function pdfResponse(): Response
    {
        abort_unless($this->pdf_path, 404);

        return Storage::disk($this->pdf_disk)->response($this->pdf_path, "Certificado {$this->number}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
