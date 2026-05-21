<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyRequest;
use App\Models\Artwork;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

    public function verify(VerifyRequest $request): JsonResponse
    {
        $artwork = Artwork::findOrFail($request->input('artwork_id'));
        $image = $request->file('image');

        $result = $this->callAiService($artwork->id, $image);
        $fallback = false;

        if ($result === null) {
            $fallback = true;
            $result = $this->simulatedResult($artwork, $image->getRealPath());
        }

        $cert = Certificate::firstOrNew(['artwork_id' => $artwork->id]);
        $cert->biometric_hash = $result['biometric_hash'] ?? hash_file('sha256', $image->getRealPath());
        $cert->match_percentage = $result['match_percentage'] ?? 0;
        if (! $cert->verification_token) {
            $cert->verification_token = Str::uuid()->toString();
        }
        $cert->save();
        $certificate = $cert;

        $matchPercentage = (float) $certificate->match_percentage;

        return response()->json([
            'certificate' => $certificate,
            'verification_result' => $result,
            'is_authentic' => $matchPercentage >= 85.0,
            'fallback' => $fallback,
        ], 201);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function callAiService(int $artworkId, \Illuminate\Http\UploadedFile $image): ?array
    {
        try {
            $response = Http::timeout(8)->attach(
                'image',
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName(),
            )->post('http://localhost:5000/api/verify', [
                'artwork_id' => $artworkId,
            ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json();
        } catch (Throwable $e) {
            Log::warning('AI verification service unavailable', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Deterministic simulation when the Python microservice is offline.
     * The same image always produces the same score, which is useful for demos.
     *
     * @return array<string, mixed>
     */
    private function simulatedResult(Artwork $artwork, string $imagePath): array
    {
        $hash = hash_file('sha256', $imagePath);
        $seed = hexdec(substr($hash, 0, 6));
        $base = 78 + ($seed % 2100) / 100;
        $score = round(min(99.95, $base), 2);

        return [
            'biometric_hash' => $hash,
            'match_percentage' => $score,
            'engine' => 'simulated',
            'artwork_id' => $artwork->id,
            'note' => 'AI microservice offline; deterministic fallback applied.',
        ];
    }
}
