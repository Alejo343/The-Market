<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReduceWeightRequest extends FormRequest
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
            'weight' => ['required', 'numeric', 'min:0.001', 'max:9999.999'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'weight' => 'peso',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'weight.required' => 'El peso a reducir es obligatorio',
            'weight.numeric' => 'El peso debe ser un valor numérico',
            'weight.min' => 'El peso debe ser mayor a 0',
            'weight.max' => 'El peso no puede superar 9999.999 kg',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $weightLot = $this->route('weightLot');

            if (!$weightLot) {
                return;
            }

            $requestedWeight = $this->input('weight');

            // Validar que el peso solicitado no exceda el disponible
            if ($requestedWeight > $weightLot->available_weight) {
                $validator->errors()->add(
                    'weight',
                    "El peso solicitado ({$requestedWeight} kg) excede el peso disponible ({$weightLot->available_weight} kg)"
                );
            }

            // Validar que el lote esté activo
            if (!$weightLot->active) {
                $validator->errors()->add(
                    'weight',
                    'No se puede reducir el peso de un lote inactivo'
                );
            }

            // Validar que el lote no esté vencido
            if ($weightLot->isExpired()) {
                $validator->errors()->add(
                    'weight',
                    'No se puede reducir el peso de un lote vencido'
                );
            }
        });
    }
}
