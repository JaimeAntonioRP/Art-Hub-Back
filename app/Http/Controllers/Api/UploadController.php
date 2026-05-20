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
}
