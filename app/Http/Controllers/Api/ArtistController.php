<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    public function index(): JsonResponse
    {
        $artists = Artist::withCount(['artworks as total_obras'])
            ->orderByDesc('featured')
            ->orderBy('name')
            ->get();

        return response()->json($artists);
    }

    public function show(string $slug): JsonResponse
    {
        $artist = Artist::where('slug', $slug)
            ->withCount(['artworks as total_obras'])
            ->firstOrFail();

        $artworks = $artist->artworks()
            ->with('certificate')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'artist'   => $artist,
            'artworks' => $artworks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:255|unique:artists,slug',
            'origin'            => 'required|string|max:255',
            'birth_year'        => 'nullable|integer|min:1800|max:2100',
            'death_year'        => 'nullable|integer|min:1800|max:2100',
            'specialty'         => 'required|string|max:255',
            'bio'               => 'required|string',
            'profile_image_url' => 'required|url|max:2048',
            'featured'          => 'boolean',
        ]);

        $artist = Artist::create($validated);

        return response()->json($artist, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $artist = Artist::findOrFail($id);

        $validated = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'slug'              => 'sometimes|string|max:255|unique:artists,slug,'.$id,
            'origin'            => 'sometimes|string|max:255',
            'birth_year'        => 'nullable|integer|min:1800|max:2100',
            'death_year'        => 'nullable|integer|min:1800|max:2100',
            'specialty'         => 'sometimes|string|max:255',
            'bio'               => 'sometimes|string',
            'profile_image_url' => 'sometimes|url|max:2048',
            'featured'          => 'boolean',
        ]);

        $artist->update($validated);

        return response()->json($artist->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        Artist::findOrFail($id)->delete();

        return response()->json(['message' => 'Artist deleted.']);
    }
}
