<?php

namespace App\Http\Controllers;

use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Descarga de un archivo del expediente: solo el admin y el artista de la obra.
 * La URL del archivo nunca queda expuesta de forma permanente.
 */
class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, DocumentVersion $version): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $user->can('view', $version->document->artwork), 403);

        return $version->downloadResponse();
    }
}
