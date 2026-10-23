<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Support\ArPlane;
use App\Support\Qr;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Realidad aumentada (§27): el modelo a escala de la obra y un QR para abrirla en el celular.
 * Se sirven por la API para que el visor 3D los pueda pedir desde el sitio (CORS).
 */
class ArController extends Controller
{
    public function model(int $id): Response
    {
        $artwork = $this->publishedArtwork($id);
        abort_unless(ArPlane::supports($artwork), 404, 'La obra no tiene foto principal o medidas.');

        try {
            $glb = ArPlane::forArtwork($artwork);
        } catch (RuntimeException $e) {
            report($e);
            abort(503, 'El modelo no está disponible en este momento.');
        }

        return response($glb, 200, [
            'Content-Type'        => 'model/gltf-binary',
            'Content-Disposition' => "inline; filename=\"{$artwork->code}.glb\"",
            'Cache-Control'       => 'public, max-age=86400',
            'ETag'                => '"'.ArPlane::cacheKey($artwork).'"',
        ]);
    }

    /** QR que abre la ficha en el celular, listo para «Ver en mi pared». */
    public function qr(int $id): Response
    {
        $artwork = $this->publishedArtwork($id);

        return response(Qr::png(config('app.frontend_url')."/obras/{$artwork->id}?ar=1", 8), 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function publishedArtwork(int $id): Artwork
    {
        return Artwork::published()->findOrFail($id);
    }
}
