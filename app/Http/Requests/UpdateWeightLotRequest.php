<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeightLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_per_kg' => ['sometimes', 'required', 'numeric', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_per_kg.min' => 'El precio no puede ser negativo',
        ];
    }
}
