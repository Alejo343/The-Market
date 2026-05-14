<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'price_cents' => 'nullable|integer|min:0',
            'polygon' => 'required|array',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
            'product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')
                    ->where(fn ($q) => $q->where('sku', 'like', 'DOM%')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la zona es obligatorio',
            'polygon.required' => 'El polígono de la zona es obligatorio',
            'color.regex' => 'El color debe ser un código hexadecimal válido (#RRGGBB)',
            'product_variant_id.exists' => 'El producto de domicilio seleccionado no es válido',
        ];
    }
}
