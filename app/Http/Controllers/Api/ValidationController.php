<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;

class ValidationController extends Controller
{
    /** Búsqueda pública de certificado por verification_token */
    public function showByToken(string $token): JsonResponse
    {
        $certificate = Certificate::with('artwork')->where('verification_token', $token)->firstOrFail();

        return response()->json([
            'certificate' => $certificate,
            'artwork' => $certificate->artwork,
        ]);
    }
}
