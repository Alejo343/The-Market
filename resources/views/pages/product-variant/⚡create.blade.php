<?php
use Livewire\Component;
use App\Services\ProductVariantService;
use App\Services\ProductService;
use App\Services\TaxService;

new class extends Component {
    public int $product_id = 0;
    public string $presentation = '';
    public string $sku = '';
    public string $price = '';
    public string $sale_price = '';
    public int $stock = 0;
    public int $min_stock = 10;
    public ?int $tax_id = null;

    /**
     * Obtiene productos y taxes para los selectores
     */
    public function with(ProductService $productService, TaxService $taxService): array
    {
        return [
            'products' => $productService->getBySaleType('unit'),
            'taxes' => $taxService->getAll(),
        ];
    }

    /**
     * Crea una nueva variante
     */
    public function save(ProductVariantService $variantService)
    {
        $validated = $this->validate(
            [
                'product_id' => 'required|exists:products,id',
                'presentation' => 'required|string|max:100',
                'sku' => 'nullable|string|max:50',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0|lt:price',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'tax_id' => 'nullable|exists:taxes,id',
            ],
            [
                'product_id.required' => 'El producto es obligatorio',
                'product_id.exists' => 'El producto seleccionado no existe',
                'presentation.required' => 'La presentación es obligatoria',
                'presentation.max' => 'La presentación no puede exceder 100 caracteres',
                'sku.max' => 'El SKU no puede exceder 50 caracteres',
                'price.required' => 'El precio es obligatorio',
                'price.numeric' => 'El precio debe ser un número',
                'price.min' => 'El precio debe ser mayor o igual a 0',
                'sale_price.numeric' => 'El precio de oferta debe ser un número',
                'sale_price.min' => 'El precio de oferta debe ser mayor o igual a 0',
                'sale_price.lt' => 'El precio de oferta debe ser menor al precio regular',
                'stock.required' => 'El stock es obligatorio',
                'stock.integer' => 'El stock debe ser un número entero',
                'stock.min' => 'El stock debe ser mayor o igual a 0',
                'min_stock.required' => 'El stock mínimo es obligatorio',
                'min_stock.integer' => 'El stock mínimo debe ser un número entero',
                'min_stock.min' => 'El stock mínimo debe ser mayor o igual a 0',
                'tax_id.exists' => 'El impuesto seleccionado no existe',
            ],
        );

        try {
            $variantService->create($validated);

            session()->flash('success', 'Variante creada exitosamente');
            return $this->redirect('/variants');
        } catch (\Exception $e) {
            $errorMessage = match ($e->getMessage()) {
                'INVALID_PRODUCT_TYPE' => 'El producto debe ser de tipo "unidad" para tener variantes',
                default => 'Error al crear la variante: ' . $e->getMessage(),
            };
            session()->flash('error', $errorMessage);
        }
    }

    /**
     * Cancela y regresa al listado
     */
    public function cancel()
    {
        return $this->redirect('/variants');
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nueva Variante</h1>
        <p class="text-gray-600">Crea una nueva variante para un producto vendido por unidad</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md p-6 max-w-3xl">
        <form wire:submit="save">
            <!-- Product Field -->
            <div class="mb-6">
                <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Producto <span class="text-red-600">*</span>
                </label>
                <select id="product_id" wire:model="product_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('product_id') border-red-500 @enderror">
                    <option value="0">Seleccionar producto</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Solo se muestran productos vendidos por unidad
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Presentation Field -->
                <div>
                    <label for="presentation" class="block text-sm font-medium text-gray-700 mb-2">
                        Presentación <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="presentation" wire:model="presentation"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('presentation') border-red-500 @enderror"
                        placeholder="Ej: 500ml, 1L, Paquete 6 unidades">
                    @error('presentation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- SKU Field -->
                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                        SKU / Código de Barras
                    </label>
                    <input type="text" id="sku" wire:model="sku"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('sku') border-red-500 @enderror"
                        placeholder="Código único del producto">
                    @error('sku')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Price Field -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        Precio Regular <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                        <input type="number" step="0.01" id="price" wire:model="price"
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('price') border-red-500 @enderror"
                            placeholder="0.00">
                    </div>
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sale Price Field -->
                <div>
                    <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">
                        Precio de Oferta
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                        <input type="number" step="0.01" id="sale_price" wire:model="sale_price"
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('sale_price') border-red-500 @enderror"
                            placeholder="0.00">
                    </div>
                    @error('sale_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        Opcional - Debe ser menor al precio regular
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Stock Field -->
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">
                        Stock Inicial <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="stock" wire:model="stock"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('stock') border-red-500 @enderror"
                        placeholder="0">
                    @error('stock')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Min Stock Field -->
                <div>
                    <label for="min_stock" class="block text-sm font-medium text-gray-700 mb-2">
                        Stock Mínimo <span class="text-red-600">*</span>
                    </label>
                    <input type="number" id="min_stock" wire:model="min_stock"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('min_stock') border-red-500 @enderror"
                        placeholder="10">
                    @error('min_stock')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        Se mostrará alerta cuando el stock esté por debajo de este valor
                    </p>
                </div>
            </div>

            <!-- Tax Field -->
            <div class="mb-6">
                <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Impuesto Aplicable
                </label>
                <select id="tax_id" wire:model="tax_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('tax_id') border-red-500 @enderror">
                    <option value="">Sin impuesto</option>
                    @foreach ($taxes as $tax)
                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                    @endforeach
                </select>
                @error('tax_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" wire:click="cancel"
                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                        </path>
                    </svg>
                    Crear Variante
                </button>
            </div>
        </form>
    </div>

    <!-- Help Text -->
    <div class="mt-6 max-w-3xl bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Información sobre variantes:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Las variantes permiten tener diferentes presentaciones del mismo producto (ej: Coca-Cola 500ml,
                        1L, 2L)</li>
                    <li>Cada variante puede tener su propio precio, stock y código de barras</li>
                    <li>El precio de oferta es opcional y debe ser menor al precio regular</li>
                    <li>El sistema alertará cuando el stock esté por debajo del mínimo configurado</li>
                </ul>
            </div>
        </div>
    </div>
</div>
