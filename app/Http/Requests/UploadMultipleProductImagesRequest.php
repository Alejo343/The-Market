<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMultipleProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:5120'
            ],
            'alts' => ['nullable', 'array'],
            'alts.*' => ['nullable', 'string', 'max:255'],
            'first_is_primary' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Debe subir al menos una imagen',
            'files.max' => 'No puede subir más de 10 imágenes a la vez',
            'files.*.mimes' => 'Todos los archivos deben ser imágenes',
            'files.*.max' => 'Cada imagen no puede exceder 5MB',
        ];
    }
}
