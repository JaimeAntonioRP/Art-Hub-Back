<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artwork_id' => 'required|integer|exists:artworks,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ];
    }
}
