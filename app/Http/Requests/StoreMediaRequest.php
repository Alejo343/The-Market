<?php

namespace App\Http\Requests;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,svg',
                'max:5120' // 5MB
            ],
            'type' => ['required', Rule::in(Media::getTypes())],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio',
            'file.mimes' => 'El archivo debe ser una imagen (jpg, jpeg, png, gif, webp, svg)',
            'file.max' => 'El archivo no puede exceder 5MB',
            'type.required' => 'El tipo de media es obligatorio',
            'type.in' => 'El tipo de media no es válido',
            'alt.max' => 'El texto alternativo no puede exceder 255 caracteres',
        ];
    }
}
