<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sale_type' => ['sometimes', 'required', Rule::in(['unit', 'weight'])],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio',
            'sale_type.in' => 'El tipo de venta debe ser "unit" o "weight"',
            'category_id.exists' => 'La categoría seleccionada no existe',
            'brand_id.exists' => 'La marca seleccionada no existe',
        ];
    }
}
