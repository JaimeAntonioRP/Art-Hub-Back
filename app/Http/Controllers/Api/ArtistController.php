<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArtistController extends Controller
{
    public function index(): JsonResponse
    {
        $artists = Artist::approved()
            ->withCount(['artworks as total_obras' => fn ($q) => $q->published()])
            ->orderByDesc('featured')
            ->orderBy('name')
            ->get();

        return response()->json($artists);
    }

    public function show(string $slug): JsonResponse
    {
        $artist = Artist::approved()
            ->where('slug', $slug)
            ->withCount(['artworks as total_obras' => fn ($q) => $q->published()])
            ->firstOrFail();

        $artworks = $artist->artworks()
            ->published()
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
            'profile_image_url' => ['required', 'string', 'max:2048', 'regex:#^(https?://|/)#'],
            'featured'          => 'boolean',
            ...$this->profileRules(),
        ]);

        // Lo que crea el admin queda aprobado hasta que exista el flujo de revisión.
        $validated['status'] ??= Artist::STATUS_APROBADO;

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
            'profile_image_url' => ['sometimes', 'string', 'max:2048', 'regex:#^(https?://|/)#'],
            'featured'          => 'boolean',
            ...$this->profileRules(),
        ]);

        $artist->update($validated);

        return response()->json($artist->fresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $artist = Artist::findOrFail($id);

        if ($artist->artworks()->exists()) {
            return response()->json(['message' => 'El artista tiene obras registradas. Desactívalo en lugar de eliminarlo.'], 409);
        }

        $artist->delete();

        return response()->json(['message' => 'Artist deleted.']);
    }

    /** Campos del perfil (§6) que el formulario actual aún no envía. */
    private function profileRules(): array
    {
        return [
            'artistic_name'  => 'nullable|string|max:255',
            'techniques'     => 'nullable|array',
            'techniques.*'   => 'string|max:100',
            'career'         => 'nullable|string',
            'awards'         => 'nullable|string',
            'contact_email'  => 'nullable|email|max:255',
            'contact_phone'  => 'nullable|string|max:50',
            'social_links'   => 'nullable|array',
            'social_links.*' => 'url|max:2048',
            'status'         => ['sometimes', Rule::in(Artist::STATUSES)],
        ];
    }
}
