<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del impuesto es obligatorio',
            'percentage.numeric' => 'El porcentaje debe ser un número',
            'percentage.min' => 'El porcentaje no puede ser negativo',
            'percentage.max' => 'El porcentaje no puede ser mayor a 100',
        ];
    }
}
