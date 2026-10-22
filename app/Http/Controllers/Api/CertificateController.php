<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal público de verificación (§20, §22).
 * - Por token (el del QR): estado, datos certificados, huellas y descarga del PDF.
 * - Por número: estado y datos, sin el PDF (los números se pueden adivinar; el token no).
 * - Por huella: dice si un PDF corresponde a algún certificado emitido. El archivo
 *   nunca se sube: el navegador calcula la huella y solo envía ese valor.
 */
class CertificateController extends Controller
{
    public function show(string $key): JsonResponse
    {
        $byToken = Certificate::where('verification_token', $key)->first();
        $certificate = $byToken ?? Certificate::where('number', strtoupper($key))->firstOrFail();

        return response()->json($this->present($certificate, withPdf: (bool) $byToken));
    }

    public function pdf(string $token): Response
    {
        return Certificate::where('verification_token', $token)->firstOrFail()->pdfResponse();
    }

    public function byFingerprint(string $sha256): JsonResponse
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $sha256), 422, 'La huella debe ser un SHA-256 en hexadecimal.');

        $certificate = Certificate::where('pdf_sha256', $sha256)->first();

        return response()->json([
            'match' => (bool) $certificate,
            'certificate' => $certificate ? $this->present($certificate, withPdf: false) : null,
        ]);
    }

    private function present(Certificate $certificate, bool $withPdf): array
    {
        $certificate->loadMissing(['artwork', 'replacedBy']);
        $artwork = $certificate->artwork;
        $published = $artwork?->status === Artwork::STATUS_PUBLICADO;

        return [
            'number'        => $certificate->number,
            'status'        => $certificate->status,
            'issued_at'     => $certificate->issued_at?->toIso8601String(),
            'revoked_at'    => $certificate->revoked_at?->toIso8601String(),
            'revoke_reason' => $certificate->revoke_reason,
            // Solo se enlaza al reemplazo por su número: quien tiene el token antiguo no obtiene el PDF nuevo.
            'replaced_by'   => $certificate->replacedBy?->number,
            'data'          => $certificate->snapshot,
            'data_sha256'   => $certificate->data_sha256,
            'pdf_sha256'    => $certificate->pdf_sha256,
            'pdf_url'       => $withPdf && $certificate->pdf_path ? url("/api/certificates/{$certificate->verification_token}/pdf") : null,
            'artwork'       => $artwork ? [
                'id'          => $published ? $artwork->id : null, // solo las publicadas tienen ficha pública
                'code'        => $artwork->code,
                'title'       => $artwork->title,
                'artist_name' => $artwork->artist_name,
                'image_url'   => $artwork->image_url,
            ] : null,
        ];
    }
}
