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
            ->where('status', 'available')
            ->orderByDesc('id')
            ->get();

        return response()->json($artworks);
    }

    public function show(int $id): JsonResponse
    {
        $artwork = Artwork::with('certificate')->findOrFail($id);

        return response()->json($artwork);
    }

    public function store(StoreArtworkRequest $request): JsonResponse
    {
        $artwork = Artwork::create($request->validated());

        return response()->json($artwork, 201);
    }

    public function adminIndex(): JsonResponse
    {
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

        if (request()->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $artwork->delete();

        return response()->json(['message' => 'Artwork deleted.']);
    }
}
