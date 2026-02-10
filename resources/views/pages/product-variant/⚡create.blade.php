<?php
use Livewire\Component;
use App\Services\ProductVariantService;
use App\Services\ProductService;
use App\Services\TaxService;
use Picqer\Barcode\BarcodeGeneratorSVG;

new class extends Component {
    public int $product_id = 0;
    public string $presentation = '';
    public string $sku = '';
    public string $skuPrefix = ''; // Prefijo auto-generado (solo lectura)
    public string $skuValue = ''; // Valor editable por usuario
    public string $barcode = '';
    public bool $showBarcodePreview = false;
    public string $price = '';
    public ?string $sale_price = null;
    public int $stock = 0;
    public int $min_stock = 10;
    public ?int $tax_id = null;

    /**
     * Genera un código de barras EAN-13 aleatorio o muestra el existente
     */
    public function generateBarcode()
    {
        // Si el campo está vacío, generar código aleatorio
        if (empty($this->barcode)) {
            // Generar 12 dígitos aleatorios
            $code = '';
            for ($i = 0; $i < 12; $i++) {
                $code .= random_int(0, 9);
            }

            // Calcular dígito verificador EAN-13
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += $i % 2 === 0 ? (int) $code[$i] : (int) $code[$i] * 3;
            }
            $checkDigit = (10 - ($sum % 10)) % 10;

            $this->barcode = $code . $checkDigit;
        }

        // Activar preview (tanto para código generado como escrito manualmente)
        $this->showBarcodePreview = true;
    }

    /**
     * Genera SVG del código de barras usando librería profesional
     */
    public function generateBarcodeSvg(string $code): string
    {
        if (empty($code)) {
            return '';
        }

        try {
            $generator = new BarcodeGeneratorSVG();
            return $generator->getBarcode($code, $generator::TYPE_EAN_13);
        } catch (\Exception $e) {
            // Si falla EAN-13, intentar CODE128
            try {
                return $generator->getBarcode($code, $generator::TYPE_CODE_128);
            } catch (\Exception $e) {
                return '';
            }
        }
    }

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
     * Al cambiar el producto, auto-generar el prefijo del SKU
     */
    public function updatedProductId()
    {
        if ($this->product_id > 0) {
            $product = \App\Models\Product::with(['category', 'brand'])->find($this->product_id);

            if ($product) {
                // Categoría: 3 primeras letras (o las que tenga)
                $category = strtoupper(substr($product->category->name ?? 'GEN', 0, 3));

                // Marca: 3 primeras letras (o las que tenga), si no tiene = GEN
                $brand = $product->brand ? strtoupper(substr($product->brand->name, 0, 3)) : 'GEN';

                // Tipo: UN (unidad) o KG (peso)
                $type = $product->sale_type === 'unit' ? 'UN' : 'KG';

                // Generar prefijo
                $this->skuPrefix = "{$category}-{$brand}-{$type}-";

                // Actualizar SKU completo
                $this->updateFullSku();
            }
        } else {
            $this->skuPrefix = '';
            $this->updateFullSku();
        }
    }

    /**
     * Al cambiar el valor del SKU, actualizar el SKU completo
     */
    public function updatedSkuValue()
    {
        $this->updateFullSku();
    }

    /**
     * Actualiza el SKU completo combinando prefijo + valor
     */
    private function updateFullSku()
    {
        $this->sku = $this->skuPrefix . strtoupper($this->skuValue);
    }

    /**
     * Crea una nueva variante
     */
    public function save(ProductVariantService $variantService)
    {
        // Normalizar valores vacíos a null
        $this->sale_price = $this->sale_price === '' ? null : $this->sale_price;
        $this->barcode = $this->barcode === '' ? null : $this->barcode;

        $validated = $this->validate(
            [
                'product_id' => 'required|exists:products,id',
                'presentation' => 'required|string|max:255',
                'sku' => 'required|string|max:255|unique:product_variants,sku',
                'barcode' => 'nullable|string|max:255|unique:product_variants,barcode',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0|lt:price',
                'stock' => 'required|integer|min:0',
                'min_stock' => 'required|integer|min:0',
                'tax_id' => 'required|exists:taxes,id',
            ],
            [
                'product_id.required' => 'El producto es obligatorio',
                'product_id.exists' => 'El producto seleccionado no existe',
                'presentation.required' => 'La presentación es obligatoria',
                'presentation.max' => 'La presentación no puede exceder 255 caracteres',
                'sku.required' => 'El SKU es obligatorio',
                'sku.max' => 'El SKU no puede exceder 255 caracteres',
                'sku.unique' => 'El SKU ya está en uso',
                'barcode.max' => 'El código de barras no puede exceder 255 caracteres',
                'barcode.unique' => 'El código de barras ya está en uso',
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
            // Normalizar campos nullable antes de enviar al service
            if (empty($validated['sale_price'])) {
                $validated['sale_price'] = null;
            }
            if (empty($validated['barcode'])) {
                $validated['barcode'] = null;
            }

            $variantService->create($validated);

            session()->flash('success', 'Variante creada exitosamente');
            return $this->redirect('/product-variants');
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
            <!-- Hidden field para SKU completo -->
            <input type="hidden" wire:model="sku">

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
                        SKU <span class="text-red-600">*</span>
                    </label>
                    <div class="flex gap-2">
                        <!-- Prefijo auto-generado (solo lectura) -->
                        <input type="text" value="{{ $skuPrefix }}" readonly
                            class="w-32 px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-700 font-mono text-sm"
                            placeholder="XXX-XXX-XX-">

                        <!-- Valor editable por usuario -->
                        <input type="text" id="skuValue" wire:model.live="skuValue"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('sku') border-red-500 @enderror"
                            placeholder="Ej: 500ML, 1L, etc">
                    </div>
                    @error('sku')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        @if ($skuPrefix)
                            SKU completo: <span class="font-mono font-semibold">{{ $sku }}</span>
                        @else
                            Selecciona un producto para generar el prefijo automáticamente
                        @endif
                    </p>
                </div>
            </div>

            <!-- Barcode Field -->
            <div class="mb-6">
                <label for="barcode" class="block text-sm font-medium text-gray-700 mb-2">
                    Código de Barras (Opcional)
                </label>
                <div class="flex gap-2">
                    <input type="text" id="barcode" wire:model="barcode"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('barcode') border-red-500 @enderror"
                        placeholder="Escribe el código o genera uno automático">
                    <button type="button" wire:click="generateBarcode"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                            </path>
                        </svg>
                        Mostrar/Generar
                    </button>
                </div>
                @error('barcode')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Escribe tu código manualmente o haz clic en "Mostrar/Generar" para crear uno automático si el campo
                    está vacío
                </p>

                <!-- Barcode Preview -->
                @if ($showBarcodePreview && $barcode)
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Vista previa del código:</span>
                            <div class="flex gap-2">
                                <a href="data:image/svg+xml;base64,{{ base64_encode($this->generateBarcodeSvg($barcode)) }}"
                                    download="barcode-{{ $barcode }}.svg"
                                    class="text-sm text-blue-600 hover:text-blue-700 inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Descargar SVG
                                </a>
                                <button type="button" wire:click="$set('showBarcodePreview', false)"
                                    class="text-sm text-gray-600 hover:text-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded text-center">
                            <img src="data:image/svg+xml;base64,{{ base64_encode($this->generateBarcodeSvg($barcode)) }}"
                                alt="Código de barras" class="mx-auto" style="max-width: 300px;">
                            <p class="text-sm font-mono text-gray-600 mt-2">{{ $barcode }}</p>
                        </div>
                    </div>
                @endif
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
