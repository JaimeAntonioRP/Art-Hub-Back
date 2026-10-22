<?php

namespace App\Support;

use App\Models\Artwork;
use App\Models\Certificate;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Http;
use Throwable;

/** PDF del certificado (§19). El QR se genera aquí mismo: ningún servicio externo ve el enlace. */
class CertificatePdf
{
    public static function render(Certificate $certificate, Artwork $artwork): string
    {
        return Pdf::loadView('pdf.certificate', [
            'certificate'   => $certificate,
            'artwork'       => $artwork,
            'data'          => $certificate->snapshot['obra'],
            'documents'     => $certificate->snapshot['documentos'],
            'categoryLabels'=> Document::CATEGORY_LABELS,
            'image'         => self::primaryImage($artwork),
            'qr'            => self::qr($certificate->verificationUrl()),
            'verifyUrl'     => $certificate->verificationUrl(),
        ])->setPaper('a4')
            ->setOption(['isFontSubsettingEnabled' => true, 'isRemoteEnabled' => false])
            ->output();
    }

    /** QR como imagen PNG embebida (data URI). */
    public static function qr(string $url): string
    {
        return (new QRCode(new QROptions([
            'outputType'    => QROutputInterface::GDIMAGE_PNG,
            'outputBase64'  => true,
            'eccLevel'      => EccLevel::M,
            'scale'         => 6,
            'quietzoneSize' => 2,
        ])))->render($url);
    }

    /**
     * Imagen principal embebida en el PDF. Las subidas se leen del disco de imágenes;
     * las antiguas (rutas del sitio) se piden al frontend. Si no se consigue, el PDF sale sin foto.
     */
    private static function primaryImage(Artwork $artwork): ?string
    {
        $image = $artwork->images->firstWhere('is_primary', true);
        if (! $image) {
            return null;
        }

        try {
            if ($image->isStoredFile()) {
                $contents = Disks::media()->get($image->path);
            } else {
                $url = str_starts_with($image->path, '/') ? config('app.frontend_url').$image->path : $image->path;
                $response = Http::timeout(5)->get($url);
                $contents = $response->successful() ? $response->body() : null;
            }
        } catch (Throwable) {
            return null;
        }

        if (! $contents || ! ($info = @getimagesizefromstring($contents)) || ! in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
            return null; // dompdf no dibuja WebP: mejor sin foto que un PDF roto
        }

        return 'data:'.$info['mime'].';base64,'.base64_encode($contents);
    }
}
