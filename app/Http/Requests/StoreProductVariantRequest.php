<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'presentation' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:product_variants,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['integer', 'min:0'],
            'min_stock' => ['integer', 'min:0'],
            'tax_id' => ['required', 'exists:taxes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio',
            'product_id.exists' => 'El producto seleccionado no existe',
            'presentation.required' => 'La presentación es obligatoria',
            'sku.unique' => 'El SKU ya está en uso',
            'price.required' => 'El precio es obligatorio',
            'price.min' => 'El precio no puede ser negativo',
            'sale_price.lt' => 'El precio de oferta debe ser menor al precio regular',
            'stock.min' => 'El stock no puede ser negativo',
            'tax_id.required' => 'El impuesto es obligatorio',
            'tax_id.exists' => 'El impuesto seleccionado no existe',
        ];
    }
}
