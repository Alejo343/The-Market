<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeightLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'initial_weight' => ['required', 'numeric', 'min:0.001', 'max:9999.999'],
            'price_per_kg' => ['required', 'numeric', 'min:0'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio',
            'product_id.exists' => 'El producto seleccionado no existe',
            'initial_weight.required' => 'El peso inicial es obligatorio',
            'initial_weight.min' => 'El peso debe ser mayor a 0',
            'initial_weight.max' => 'El peso no puede exceder 9999.999 kg',
            'price_per_kg.required' => 'El precio por kilo es obligatorio',
            'price_per_kg.min' => 'El precio no puede ser negativo',
            'expires_at.after' => 'La fecha de vencimiento debe ser posterior a hoy',
        ];
    }

    /**
     * Obtener los datos validados con available_weight incluido
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Agregar available_weight igual a initial_weight
        $validated['available_weight'] = $validated['initial_weight'];

        return $validated;
    }
}
