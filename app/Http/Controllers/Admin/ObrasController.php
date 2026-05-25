<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ObrasController extends Controller
{
    public function index(Request $request)
    {
        $obras = Artwork::query()
            ->when($request->q, fn($q, $search) =>
                $q->where('title', 'like', "%$search%")
                  ->orWhere('artist_name', 'like', "%$search%")
            )
            ->latest()
            ->paginate(20);

        return view('admin.obras.index', compact('obras'));
    }

    public function create()
    {
        $artistas = Artist::orderBy('name')->get();
        return view('admin.obras.form', compact('artistas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'price'       => 'nullable|numeric|min:0',
            'year'        => 'nullable|integer|min:1800|max:2100',
            'technique'   => 'nullable|string|max:255',
            'dimensions'  => 'nullable|string|max:255',
            'status'      => 'nullable|in:available,sold,reserved,hidden',
            'image_url'   => 'nullable|url',
            'model_3d_url'=> 'nullable|url',
            'description' => 'nullable|string',
        ]);

        $data['status'] = $data['status'] ?? 'available';
        Artwork::create($data);

        return redirect()->route('admin.obras.index')
                         ->with('success', 'Obra creada correctamente.');
    }

    public function edit(Artwork $obra)
    {
        $artistas = Artist::orderBy('name')->get();
        return view('admin.obras.form', compact('obra', 'artistas'));
    }

    public function update(Request $request, Artwork $obra)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'price'       => 'nullable|numeric|min:0',
            'year'        => 'nullable|integer|min:1800|max:2100',
            'technique'   => 'nullable|string|max:255',
            'dimensions'  => 'nullable|string|max:255',
            'status'      => 'nullable|in:available,sold,reserved,hidden',
            'image_url'   => 'nullable|url',
            'model_3d_url'=> 'nullable|url',
            'description' => 'nullable|string',
        ]);

        $obra->update($data);

        return redirect()->route('admin.obras.index')
                         ->with('success', 'Obra actualizada.');
    }

    public function destroy(Artwork $obra)
    {
        $obra->delete();
        return back()->with('success', 'Obra eliminada.');
    }
}
