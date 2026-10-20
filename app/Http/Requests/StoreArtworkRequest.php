<?php

namespace App\Http\Requests;

use App\Models\Artwork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        // Acepta URLs completas (http/https) o rutas locales que empiezan con "/"
        $urlOrPath = 'regex:#^(https?://|/)#';

        return [
            'title'         => 'required|string|max:255',
            // artist_name se mantiene mientras los paneles actuales envíen el nombre.
            'artist_id'     => 'required_without:artist_name|nullable|integer|exists:artists,id',
            'artist_name'   => 'required_without:artist_id|nullable|string|max:255|exists:artists,name',
            'price'         => 'nullable|numeric|min:0',
            'currency'      => ['sometimes', Rule::in(Artwork::CURRENCIES)],
            'show_price'    => 'sometimes|boolean',
            'description'   => 'nullable|string',
            'technique'     => 'nullable|string|max:255',
            'support'       => 'nullable|string|max:255',
            'year'          => 'nullable|integer|min:1000|max:2100',
            'height_cm'     => 'nullable|numeric|min:0',
            'width_cm'      => 'nullable|numeric|min:0',
            'depth_cm'      => 'nullable|numeric|min:0',
            'weight_kg'     => 'nullable|numeric|min:0',
            'dimensions'    => 'nullable|string|max:255',
            'availability'  => ['sometimes', Rule::in(Artwork::AVAILABILITIES)],
            'status'        => ['sometimes', Rule::in(Artwork::STATUSES)],
            'location'      => 'nullable|string|max:255',
            'history_notes' => 'nullable|string',
            'image_url'     => ['nullable', 'string', 'max:2048', $urlOrPath],
            'model_3d_url'  => ['nullable', 'string', 'max:2048', $urlOrPath],
        ];
    }
}
