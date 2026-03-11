<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeightLotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'initial_weight' => ['required', 'numeric', 'min:0.001', 'max:9999.999'],
            'price_per_kg' => ['required', 'numeric', 'min:0.01'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'active' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'initial_weight' => 'peso inicial',
            'price_per_kg' => 'precio por kg',
            'expires_at' => 'fecha de vencimiento',
            'active' => 'activo',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio',
            'product_id.exists' => 'El producto seleccionado no existe',
            'initial_weight.required' => 'El peso inicial es obligatorio',
            'initial_weight.min' => 'El peso inicial debe ser mayor a 0',
            'initial_weight.max' => 'El peso inicial no puede superar 9999.999 kg',
            'price_per_kg.required' => 'El precio por kg es obligatorio',
            'price_per_kg.min' => 'El precio por kg debe ser mayor a 0',
            'expires_at.after' => 'La fecha de vencimiento debe ser posterior a hoy',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Establecer available_weight igual a initial_weight en la creación
        if ($this->has('initial_weight')) {
            $this->merge([
                'available_weight' => $this->input('initial_weight'),
            ]);
        }

        // Establecer active como true por defecto si no se proporciona
        if (!$this->has('active')) {
            $this->merge([
                'active' => true,
            ]);
        }
    }
}
