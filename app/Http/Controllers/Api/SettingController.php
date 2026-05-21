<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /** Claves permitidas para edición de marca */
    private const ALLOWED = ['brand_name', 'brand_tagline', 'logo_url'];

    public function index(): JsonResponse
    {
        return response()->json(Setting::map());
    }

    public function update(Request $request): JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'brand_name'    => 'nullable|string|max:120',
            'brand_tagline' => 'nullable|string|max:160',
            'logo_url'      => ['nullable', 'string', 'max:2048', 'regex:#^(https?://|/)#'],
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, self::ALLOWED, true)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return response()->json(Setting::map());
    }
}
