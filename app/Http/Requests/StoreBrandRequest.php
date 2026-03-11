<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la marca es obligatorio',
            'name.unique' => 'Ya existe una marca con ese nombre',
        ];
    }
}
