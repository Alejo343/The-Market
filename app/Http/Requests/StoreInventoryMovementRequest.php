<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_type' => ['required', 'in:variant,weight_lot'],
            'item_id' => ['required', 'integer'],
            'type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_type.required' => 'El tipo de item es obligatorio',
            'item_type.in' => 'El tipo debe ser "variant" o "weight_lot"',
            'item_id.required' => 'El ID del item es obligatorio',
            'type.required' => 'El tipo de movimiento es obligatorio',
            'type.in' => 'El tipo debe ser "in", "out" o "adjustment"',
            'quantity.required' => 'La cantidad es obligatoria',
            'quantity.min' => 'La cantidad debe ser mayor a 0',
            'note.max' => 'La nota no puede exceder 500 caracteres',
        ];
    }
}
