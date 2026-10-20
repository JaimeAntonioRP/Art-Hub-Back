<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtworkRequest;
use App\Models\Artwork;
use Illuminate\Http\JsonResponse;

class ArtworkController extends Controller
{
    public function index(): JsonResponse
    {
        $artworks = Artwork::with('certificate')
            ->published()
            ->orderByDesc('id')
            ->get();

        return response()->json($artworks);
    }

    public function show(int $id): JsonResponse
    {
        $artwork = Artwork::with('certificate')->findOrFail($id);

        if ($artwork->status !== Artwork::STATUS_PUBLICADO && ! auth('sanctum')->user()?->isAdmin()) {
            abort(404);
        }

        return response()->json($artwork);
    }

    public function store(StoreArtworkRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Hasta que exista el flujo de revisión, lo que crea el admin se publica directo.
        $data['status'] ??= Artwork::STATUS_PUBLICADO;

        $artwork = Artwork::create($data);

        return response()->json($artwork->fresh()->load('certificate'), 201);
    }

    public function adminIndex(): JsonResponse
    {
        if (! request()->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $artworks = Artwork::with('certificate')->orderByDesc('id')->get();

        return response()->json($artworks);
    }

    public function update(StoreArtworkRequest $request, int $id): JsonResponse
    {
        $artwork = Artwork::findOrFail($id);
        $artwork->update($request->validated());

        return response()->json($artwork->fresh()->load('certificate'));
    }

    public function destroy(int $id): JsonResponse
    {
        $artwork = Artwork::findOrFail($id);

        if (! request()->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $artwork->delete();

        return response()->json(['message' => 'Artwork deleted.']);
    }
}
