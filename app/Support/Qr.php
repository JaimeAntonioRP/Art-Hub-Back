<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/** Códigos QR generados en el servidor: ningún servicio externo ve los enlaces. */
class Qr
{
    /** PNG binario. */
    public static function png(string $url, int $scale = 6): string
    {
        return self::render($url, $scale, base64: false);
    }

    /** PNG como data URI, para incrustarlo (por ejemplo, en un PDF). */
    public static function dataUri(string $url, int $scale = 6): string
    {
        return self::render($url, $scale, base64: true);
    }

    private static function render(string $url, int $scale, bool $base64): string
    {
        return (new QRCode(new QROptions([
            'outputType'    => QROutputInterface::GDIMAGE_PNG,
            'outputBase64'  => $base64,
            'eccLevel'      => EccLevel::M,
            'scale'         => $scale,
            'quietzoneSize' => 2,
        ])))->render($url);
    }
}
