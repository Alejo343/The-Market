<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'       => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'alt'        => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
            'order'      => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debe subir una imagen',
            'file.mimes'    => 'El archivo debe ser una imagen (jpg, jpeg, png, gif, webp)',
            'file.max'      => 'La imagen no puede exceder 5MB',
        ];
    }
}
