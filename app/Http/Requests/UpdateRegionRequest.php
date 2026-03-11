<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regionId = $this->route('region');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('regions', 'name')->ignore($regionId)
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la región es obligatorio',
            'name.unique' => 'Ya existe una región con ese nombre',
            'name.max' => 'El nombre no puede exceder 255 caracteres',
            'description.max' => 'La descripción no puede exceder 500 caracteres',
        ];
    }
}
