<?php

namespace App\Http\Requests;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', Rule::in(Media::getTypes())],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de media no es válido',
            'alt.max' => 'El texto alternativo no puede exceder 255 caracteres',
        ];
    }
}
