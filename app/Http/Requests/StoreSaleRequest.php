<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', 'in:store,online'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:variant,weight_lot'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ];
    }

    public function messages(): array
    {
        return [
            'channel.required' => 'El canal de venta es obligatorio',
            'channel.in' => 'El canal debe ser "store" o "online"',
            'items.required' => 'Debe agregar al menos un item a la venta',
            'items.min' => 'Debe agregar al menos un item a la venta',
            'items.*.type.required' => 'El tipo de item es obligatorio',
            'items.*.type.in' => 'El tipo debe ser "variant" o "weight_lot"',
            'items.*.id.required' => 'El ID del item es obligatorio',
            'items.*.quantity.required' => 'La cantidad es obligatoria',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0',
        ];
    }
}
