<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        // Acepta URLs completas (http/https) o rutas locales que empiezan con "/"
        $urlOrPath = 'regex:#^(https?://|/)#';

        return [
            'title' => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'image_url' => ['required', 'string', 'max:2048', $urlOrPath],
            'model_3d_url' => ['nullable', 'string', 'max:2048', $urlOrPath],
        ];
    }
}
