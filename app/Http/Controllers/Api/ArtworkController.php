<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArtworkRequest;
use App\Models\Artist;
use App\Models\Artwork;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArtworkController extends Controller
{
    private const SORTS = ['recent', 'price_asc', 'price_desc', 'title'];

    /**
     * Catálogo público (§12): obras publicadas con búsqueda, filtros, orden y paginado.
     * `facets` trae las opciones de los filtros (artistas, técnicas y rango de precio).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'artist' => 'nullable|string|max:255',
            'technique' => 'nullable|string|max:255',
            'availability' => ['nullable', Rule::in(Artwork::AVAILABILITIES)],
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'per_page' => 'nullable|integer|min:1|max:48',
        ]);

        $query = Artwork::published()
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $w) => $w
                ->whereLike('title', "%{$term}%")
                ->orWhereLike('code', "%{$term}%")
                ->orWhereHas('artist', fn (Builder $a) => $a->whereLike('name', "%{$term}%")->orWhereLike('artistic_name', "%{$term}%"))))
            ->when($filters['artist'] ?? null, fn (Builder $q, string $slug) => $q->whereHas('artist', fn (Builder $a) => $a->where('slug', $slug)))
            ->when($filters['technique'] ?? null, fn (Builder $q, string $t) => $q->where('technique', $t))
            ->when($filters['availability'] ?? null, fn (Builder $q, string $a) => $q->where('availability', $a))
            // Las obras con «Consultar precio» no entran en un filtro de precio.
            ->when(isset($filters['price_min']) || isset($filters['price_max']), fn (Builder $q) => $q->where('show_price', true)->whereNotNull('price'))
            ->when($filters['price_min'] ?? null, fn (Builder $q, $min) => $q->where('price', '>=', $min))
            ->when($filters['price_max'] ?? null, fn (Builder $q, $max) => $q->where('price', '<=', $max));

        match ($filters['sort'] ?? 'recent') {
            'price_asc' => $query->orderByRaw('price is null')->orderBy('price'),
            'price_desc' => $query->orderByRaw('price is null')->orderByDesc('price'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $page = $query->paginate($filters['per_page'] ?? 12)->withQueryString();

        return response()->json([
            'data' => collect($page->items())->map->toCard()->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
            'facets' => $this->facets(),
        ]);
    }

    /** Ficha pública (§13). El admin conectado también puede previsualizar obras no publicadas. */
    public function show(int $id): JsonResponse
    {
        $artwork = Artwork::with(['certificate', 'artist'])->findOrFail($id);

        if ($artwork->status !== Artwork::STATUS_PUBLICADO && ! auth('sanctum')->user()?->isAdmin()) {
            abort(404);
        }

        $related = Artwork::published()
            ->where('artist_id', $artwork->artist_id)
            ->whereKeyNot($artwork->id)
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map->toCard();

        return response()->json([
            ...$artwork->toCard(),
            'support' => $artwork->support,
            'height_cm' => $artwork->height_cm,
            'width_cm' => $artwork->width_cm,
            'depth_cm' => $artwork->depth_cm,
            'weight_kg' => $artwork->weight_kg,
            'description' => $artwork->description,
            'history_notes' => $artwork->history_notes,
            'status' => $artwork->status,
            'images' => $artwork->images->map(fn ($i) => $i->only(['id', 'kind', 'url', 'is_primary']))->values(),
            'artist' => $artwork->artist ? [
                ...$artwork->artist->only(['name', 'artistic_name', 'slug', 'origin', 'specialty', 'photo_url']),
                // Solo los perfiles aprobados tienen página pública.
                'has_page' => $artwork->artist->status === Artist::STATUS_APROBADO,
            ] : null,
            'certificate' => $artwork->certificate?->only(['verification_token', 'status']),
            'related' => $related,
        ]);
    }

    public function store(StoreArtworkRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Lo que crea el admin por la API se publica directo.
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

    /** Opciones de los filtros, calculadas sobre todo el catálogo publicado. */
    private function facets(): array
    {
        $published = fn () => Artwork::published()->withoutEagerLoads();

        return [
            'artists' => Artist::query()
                ->whereHas('artworks', fn (Builder $q) => $q->published())
                ->withCount(['artworks as total' => fn (Builder $q) => $q->published()])
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Artist $a) => ['slug' => $a->slug, 'name' => $a->name, 'total' => $a->total])
                ->all(),
            'techniques' => $published()->whereNotNull('technique')->distinct()->orderBy('technique')->pluck('technique')->all(),
            'price' => [
                'min' => (float) ($published()->where('show_price', true)->min('price') ?? 0),
                'max' => (float) ($published()->where('show_price', true)->max('price') ?? 0),
            ],
        ];
    }
}
