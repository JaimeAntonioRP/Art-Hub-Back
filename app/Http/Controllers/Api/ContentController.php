<?php

namespace App\Http\Controllers\Api;

use App\Filament\Schemas\PageBlocks;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SiteContent;
use Illuminate\Http\JsonResponse;

/** Contenido editable del sitio público: ajustes globales y páginas por bloques. */
class ContentController extends Controller
{
    public function site(): JsonResponse
    {
        return response()->json(SiteContent::forApi());
    }

    public function page(string $slug): JsonResponse
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'slug' => $page->slug,
            'title' => $page->title,
            'description' => $page->description,
            'blocks' => PageBlocks::forApi($page->blocks),
        ]);
    }
}
