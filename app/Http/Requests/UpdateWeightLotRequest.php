<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeightLotRequest extends FormRequest
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
            'initial_weight' => ['sometimes', 'numeric', 'min:0.001', 'max:9999.999'],
            'available_weight' => ['sometimes', 'numeric', 'min:0', 'max:9999.999', 'lte:initial_weight'],
            'price_per_kg' => ['sometimes', 'numeric', 'min:0.01'],
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
            'initial_weight' => 'peso inicial',
            'available_weight' => 'peso disponible',
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
            'initial_weight.min' => 'El peso inicial debe ser mayor a 0',
            'initial_weight.max' => 'El peso inicial no puede superar 9999.999 kg',
            'available_weight.min' => 'El peso disponible no puede ser negativo',
            'available_weight.max' => 'El peso disponible no puede superar 9999.999 kg',
            'available_weight.lte' => 'El peso disponible no puede ser mayor al peso inicial',
            'price_per_kg.min' => 'El precio por kg debe ser mayor a 0',
            'expires_at.after' => 'La fecha de vencimiento debe ser posterior a hoy',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que available_weight no sea mayor que initial_weight
            if ($this->has('available_weight') && $this->has('initial_weight')) {
                if ($this->input('available_weight') > $this->input('initial_weight')) {
                    $validator->errors()->add(
                        'available_weight',
                        'El peso disponible no puede ser mayor al peso inicial'
                    );
                }
            }

            // Si solo se actualiza available_weight, verificar contra el initial_weight del modelo
            if ($this->has('available_weight') && !$this->has('initial_weight')) {
                $weightLot = $this->route('weightLot');
                if ($weightLot && $this->input('available_weight') > $weightLot->initial_weight) {
                    $validator->errors()->add(
                        'available_weight',
                        'El peso disponible no puede ser mayor al peso inicial del lote'
                    );
                }
            }
        });
    }
}
