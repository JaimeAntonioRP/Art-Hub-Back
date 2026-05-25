<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArtistasController extends Controller
{
    public function index(Request $request)
    {
        $artistas = Artist::query()
            ->when($request->q, fn($q, $search) =>
                $q->where('name', 'like', "%$search%")
                  ->orWhere('origin', 'like', "%$search%")
            )
            ->orderBy('name')
            ->paginate(20);

        return view('admin.artistas.index', compact('artistas'));
    }

    public function create()
    {
        return view('admin.artistas.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:artists,slug',
            'origin'            => 'nullable|string|max:255',
            'specialty'         => 'nullable|string|max:255',
            'birth_year'        => 'nullable|integer|min:1800|max:2100',
            'death_year'        => 'nullable|integer|min:1800|max:2100',
            'bio'               => 'nullable|string',
            'profile_image_url' => 'nullable|url',
            'featured'          => 'nullable|boolean',
        ]);

        $data['slug']     = $data['slug'] ?: Str::slug($data['name']);
        $data['featured'] = $request->boolean('featured');

        Artist::create($data);

        return redirect()->route('admin.artistas.index')
                         ->with('success', 'Artista creado correctamente.');
    }

    public function edit(Artist $artista)
    {
        return view('admin.artistas.form', compact('artista'));
    }

    public function update(Request $request, Artist $artista)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|unique:artists,slug,'.$artista->id,
            'origin'            => 'nullable|string|max:255',
            'specialty'         => 'nullable|string|max:255',
            'birth_year'        => 'nullable|integer|min:1800|max:2100',
            'death_year'        => 'nullable|integer|min:1800|max:2100',
            'bio'               => 'nullable|string',
            'profile_image_url' => 'nullable|url',
            'featured'          => 'nullable|boolean',
        ]);

        $data['slug']     = $data['slug'] ?: Str::slug($data['name']);
        $data['featured'] = $request->boolean('featured');

        $artista->update($data);

        return redirect()->route('admin.artistas.index')
                         ->with('success', 'Artista actualizado.');
    }

    public function destroy(Artist $artista)
    {
        $artista->delete();
        return back()->with('success', 'Artista eliminado.');
    }
}
