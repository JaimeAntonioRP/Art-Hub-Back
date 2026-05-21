<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function image(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $path = $request->file('image')->store('artworks', 'public');

        return response()->json([
            'path' => $path,
            'url' => url('storage/'.$path),
        ], 201);
    }

    public function model(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $file = $request->file('model');
        if (! $file) {
            return response()->json(['message' => 'No se recibió ningún archivo.'], 422);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['glb', 'gltf'], true)) {
            return response()->json(['message' => 'Solo se permiten archivos .glb o .gltf'], 422);
        }

        if ($file->getSize() > 50 * 1024 * 1024) {
            return response()->json(['message' => 'El archivo no puede superar los 50 MB.'], 422);
        }

        $path = $file->storeAs('models', uniqid('model_', true).'.'.$ext, 'public');

        return response()->json([
            'path' => $path,
            'url'  => url('storage/'.$path),
        ], 201);
    }
}
