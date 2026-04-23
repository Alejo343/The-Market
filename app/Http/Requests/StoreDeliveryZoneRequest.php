<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:100',
            'color'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'price_cents'=> 'required|integer|min:0',
            'polygon'    => 'required|array',
            'sort_order' => 'nullable|integer|min:0',
            'active'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'El nombre de la zona es obligatorio',
            'price_cents.required' => 'El costo de envío es obligatorio',
            'price_cents.integer'  => 'El costo debe ser un entero en centavos',
            'price_cents.min'      => 'El costo no puede ser negativo',
            'polygon.required'     => 'El polígono de la zona es obligatorio',
            'color.regex'          => 'El color debe ser un código hexadecimal válido (#RRGGBB)',
        ];
    }
}
