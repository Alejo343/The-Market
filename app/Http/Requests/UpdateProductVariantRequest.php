<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('product_variant');

        return [
            'product_id' => ['sometimes', 'required', 'exists:products,id'],
            'presentation' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')->ignore($variantId)
            ],
            'barcode' => [ // ← AGREGAR
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants', 'barcode')->ignore($variantId)
            ],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['integer', 'min:0'],
            'min_stock' => ['integer', 'min:0'],
            'tax_id' => ['sometimes', 'required', 'exists:taxes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => 'El producto seleccionado no existe',
            'presentation.required' => 'La presentación es obligatoria',
            'sku.unique' => 'El SKU ya está en uso',
            'price.min' => 'El precio no puede ser negativo',
            'stock.min' => 'El stock no puede ser negativo',
            'tax_id.exists' => 'El impuesto seleccionado no existe',
        ];
    }
}
