<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ProductVariantService;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\RegionService;
use App\Services\TaxService;
use App\Models\ProductVariant;
use Picqer\Barcode\BarcodeGeneratorSVG;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $filterCategoryId = null;
    public ?int $filterRegionId = null;
    public bool $showLowStockOnly = false;
    public bool $showOutOfStockOnly = false;
    public bool $showInStockOnly = false;
    public bool $showOnSaleOnly = false;
    public int $perPage = 15;
    public string $sortDirection = 'asc';

    public ?int $editingId = null;
    public ?int $deletingId = null;
    public ?int $viewingBarcodeId = null;

    // Form fields para edición
    public int $product_id = 0;
    public string $presentation = '';
    public string $sku = '';
    public string $barcode = '';
    public string $price = '';
    public ?string $sale_price = null;
    public int $stock = 0;
    public int $min_stock = 10;
    public ?int $tax_id = null;

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener las variantes filtradas
     */
    public function with(ProductVariantService $variantService, ProductService $productService, CategoryService $categoryService, RegionService $regionService, TaxService $taxService): array
    {
        try {
            $variants = $variantService->list(categoryId: $this->filterCategoryId, regionId: $this->filterRegionId, lowStockOnly: $this->showLowStockOnly, outOfStockOnly: $this->showOutOfStockOnly, inStockOnly: $this->showInStockOnly, onSaleOnly: $this->showOnSaleOnly, search: $this->search, include: ['product.region', 'product.category', 'tax'], perPage: $this->perPage, sortDirection: $this->sortDirection);

            return [
                'variants' => $variants,
                'products' => $productService->getBySaleType('unit'),
                'categories' => $categoryService->getAll(),
                'regions' => $regionService->getActive(),
                'taxes' => $taxService->getAll(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = $this->translateError($e->getMessage());
            return [
                'variants' => collect(),
                'products' => collect(),
                'categories' => collect(),
                'regions' => collect(),
                'taxes' => collect(),
            ];
        }
    }

    /**
     * Actualiza los filtros
     */
    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedFilterCategoryId()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedFilterRegionId()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedShowLowStockOnly()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedShowOutOfStockOnly()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedShowInStockOnly()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedShowOnSaleOnly()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function toggleSort()
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    /**
     * Limpia todos los filtros
     */
    public function toggleProductActive(int $productId)
    {
        $product = \App\Models\Product::findOrFail($productId);
        $product->update(['active' => !$product->active]);
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterCategoryId = null;
        $this->filterRegionId = null;
        $this->showLowStockOnly = false;
        $this->showOutOfStockOnly = false;
        $this->showInStockOnly = false;
        $this->showOnSaleOnly = false;
        $this->resetPage();
        $this->resetMessages();
    }

    /**
     * Inicia el modo de edición
     */
    public function edit(int $id)
    {
        $this->resetMessages();
        $this->editingId = $id;

        try {
            $variant = ProductVariant::findOrFail($id);
            $this->product_id = $variant->product_id;
            $this->presentation = $variant->presentation;
            $this->sku = $variant->sku;
            $this->barcode = $variant->barcode ?? '';
            $this->price = $variant->price;
            $this->sale_price = $variant->sale_price;
            $this->stock = $variant->stock;
            $this->min_stock = $variant->min_stock;
            $this->tax_id = $variant->tax_id;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cargar la variante';
            $this->cancelEdit();
        }
    }

    /**
     * Cancela la edición
     */
    public function cancelEdit()
    {
        $this->editingId = null;
        $this->product_id = 0;
        $this->presentation = '';
        $this->sku = '';
        $this->barcode = '';
        $this->price = '';
        $this->sale_price = null;
        $this->stock = 0;
        $this->min_stock = 10;
        $this->tax_id = null;
    }

    /**
     * Guarda la variante editada
     */
    public function save(ProductVariantService $variantService)
    {
        $this->resetMessages();

        $this->validate(
            [
                'product_id' => 'required|exists:products,id',
                'presentation' => 'required|string|max:255',
                'sku' => 'required|string|max:255',
                'barcode' => 'nullable|string|max:255',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|sometimes|numeric|min:0|lt:price',
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
                'barcode.max' => 'El código de barras no puede exceder 255 caracteres',
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
                'tax_id.required' => 'El impuesto es obligatorio',
                'tax_id.exists' => 'El impuesto seleccionado no existe',
            ],
        );

        try {
            $variant = ProductVariant::findOrFail($this->editingId);

            $variantService->update($variant, [
                'product_id' => $this->product_id,
                'presentation' => $this->presentation,
                'sku' => $this->sku,
                'barcode' => $this->barcode ?: null,
                'price' => $this->price,
                'sale_price' => $this->sale_price,
                'stock' => $this->stock,
                'min_stock' => $this->min_stock,
                'tax_id' => $this->tax_id,
            ]);

            $this->successMessage = 'Variante actualizada exitosamente';
            $this->cancelEdit();
        } catch (\Exception $e) {
            $this->errorMessage = $this->translateError($e->getMessage());
        }
    }

    /**
     * Confirma la eliminación
     */
    public function confirmDelete(int $id)
    {
        $this->resetMessages();
        $this->deletingId = $id;
    }

    /**
     * Cancela la eliminación
     */
    public function cancelDelete()
    {
        $this->deletingId = null;
    }

    /**
     * Elimina la variante
     */
    public function delete(ProductVariantService $variantService)
    {
        $this->resetMessages();

        try {
            $variant = ProductVariant::findOrFail($this->deletingId);
            $variantService->delete($variant);

            $this->successMessage = 'Variante eliminada exitosamente';
            $this->deletingId = null;
        } catch (\Exception $e) {
            $this->errorMessage = $this->translateError($e->getMessage());
            $this->deletingId = null;
        }
    }

    /**
     * Resetea los mensajes de error y éxito
     */
    private function resetMessages()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    /**
     * Ver código de barras en modal
     */
    public function viewBarcode(int $id)
    {
        $this->viewingBarcodeId = $id;
    }

    /**
     * Cancelar vista de código de barras
     */
    public function cancelViewBarcode()
    {
        $this->viewingBarcodeId = null;
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
     * Traduce los mensajes de error al español
     */
    private function translateError(string $error): string
    {
        return match ($error) {
            'VARIANT_HAS_SALES' => 'No se puede eliminar: la variante tiene ventas asociadas',
            'INVALID_PRODUCT_TYPE' => 'El producto debe ser de tipo "unidad" para tener variantes',
            default => 'Error: ' . $error,
        };
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Variantes de Productos</h1>
        <p class="text-gray-600">Gestiona las variantes de productos vendidos por unidad</p>
    </div>

    <!-- Messages -->
    @if ($successMessage)
        <div
            class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', '')" class="text-green-700 hover:text-green-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    @endif

    @if ($errorMessage)
        <div
            class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', '')" class="text-red-700 hover:text-red-900">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por producto, presentación, SKU o código..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <select wire:model.live="filterCategoryId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todas las categorías</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Region Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Región</label>
                <select wire:model.live="filterRegionId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todas las regiones</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}">{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6 flex-wrap">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showLowStockOnly"
                        class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                    <span class="text-sm text-gray-700">Stock bajo</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showOutOfStockOnly"
                        class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                    <span class="text-sm text-gray-700">Sin stock</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showInStockOnly"
                        class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                    <span class="text-sm text-gray-700">En stock</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showOnSaleOnly"
                        class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                    <span class="text-sm text-gray-700">En oferta</span>
                </label>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Mostrar</label>
                    <select wire:model.live="perPage"
                        class="px-2 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-sm text-gray-600">por página</span>
                </div>

                <button wire:click="clearFilters"
                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mb-6 flex justify-end">
        <a href="/product-variants/create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nueva Variante
        </a>
    </div>

    <!-- Variants Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="toggleSort" class="inline-flex items-center gap-1 hover:text-gray-900 transition-colors">
                                Producto / Presentación
                                @if ($sortDirection === 'asc')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            SKU
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Región
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Categoría
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Precio
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stock
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($variants as $variant)
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="variant-{{ $variant->id }}">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $variant->product->name }}</div>
                                <div class="text-xs text-gray-500">{{ $variant->presentation }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $variant->sku ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $variant->product->region?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $variant->product->category?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    ${{ number_format($variant->price, 2) }}
                                </div>
                                @if ($variant->sale_price)
                                    <div class="text-xs text-purple-600 font-medium">
                                        Oferta: ${{ number_format($variant->sale_price, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $variant->stock }} unidades</div>
                                <div class="text-xs text-gray-500">Mín: {{ $variant->min_stock }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleProductActive({{ $variant->product->id }})"
                                    title="Clic para {{ $variant->product->active ? 'desactivar' : 'activar' }} el producto"
                                    class="px-2 py-1 text-xs rounded-full transition-colors {{ $variant->product->active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                    {{ $variant->product->active ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2" wire:key="action-buttons-{{ $variant->id }}">
                                    <button wire:click="edit({{ $variant->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $variant->id }})"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
                                        title="Eliminar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                @if ($search || $filterProductId || $showLowStockOnly || $showOutOfStockOnly || $showInStockOnly || $showOnSaleOnly)
                                    No se encontraron variantes con los filtros aplicados
                                @else
                                    No hay variantes registradas
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($variants instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between gap-4">
                <div class="text-sm text-gray-500">
                    @if ($variants->total() > 0)
                        Mostrando <span class="font-medium text-gray-700">{{ $variants->firstItem() }}</span>–<span
                            class="font-medium text-gray-700">{{ $variants->lastItem() }}</span>
                        de <span class="font-medium text-gray-700">{{ $variants->total() }}</span> variantes
                    @else
                        Sin resultados
                    @endif
                </div>

                @if ($variants->hasPages())
                    <div class="flex items-center gap-1">
                        @if ($variants->onFirstPage())
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed select-none">
                                ‹
                            </span>
                        @else
                            <button wire:click="previousPage" wire:loading.attr="disabled"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                ‹
                            </button>
                        @endif

                        @foreach ($variants->getUrlRange(1, $variants->lastPage()) as $page => $url)
                            @if ($page == $variants->currentPage())
                                <span
                                    class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg select-none">
                                    {{ $page }}
                                </span>
                            @elseif ($page == 1 || $page == $variants->lastPage() || abs($page - $variants->currentPage()) <= 2)
                                <button wire:click="gotoPage({{ $page }})"
                                    class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                    {{ $page }}
                                </button>
                            @elseif (abs($page - $variants->currentPage()) == 3)
                                <span class="px-2 py-1.5 text-sm text-gray-400 select-none">…</span>
                            @endif
                        @endforeach

                        @if ($variants->hasMorePages())
                            <button wire:click="nextPage" wire:loading.attr="disabled"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                ›
                            </button>
                        @else
                            <span
                                class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed select-none">
                                ›
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Edit Modal -->
    @if ($editingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Variante</h3>

                <div class="space-y-4">
                    <!-- Product -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Producto <span class="text-red-600">*</span>
                        </label>
                        <select wire:model="product_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="0">Seleccionar producto</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <span class="text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Presentation -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Presentación <span class="text-red-600">*</span>
                            </label>
                            <input type="text" wire:model="presentation"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('presentation')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- SKU -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <input type="text" wire:model="sku"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('sku')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Precio <span class="text-red-600">*</span>
                            </label>
                            <input type="number" step="0.01" wire:model="price"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('price')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Sale Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio de Oferta</label>
                            <input type="number" step="0.01" wire:model="sale_price"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('sale_price')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Stock -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Stock <span class="text-red-600">*</span>
                            </label>
                            <input type="number" wire:model="stock"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('stock')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Min Stock -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Stock Mínimo <span class="text-red-600">*</span>
                            </label>
                            <input type="number" wire:model="min_stock"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('min_stock')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Tax -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Impuesto</label>
                            <select wire:model="tax_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Sin impuesto</option>
                                @foreach ($taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)
                                    </option>
                                @endforeach
                            </select>
                            @error('tax_id')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <button wire:click="cancelEdit"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="save"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Barcode View Modal -->
    @if ($viewingBarcodeId)
        @php
            $viewingVariant = $variants->firstWhere('id', $viewingBarcodeId);
        @endphp
        @if ($viewingVariant && $viewingVariant->barcode)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Código de Barras</h3>
                            <p class="text-sm text-gray-600">{{ $viewingVariant->product->name }} -
                                {{ $viewingVariant->presentation }}</p>
                        </div>
                        <button wire:click="cancelViewBarcode" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                        <img src="data:image/svg+xml;base64,{{ base64_encode($this->generateBarcodeSvg($viewingVariant->barcode)) }}"
                            alt="Código de barras" class="mx-auto mb-4" style="max-width: 400px;">
                        <p class="text-lg font-mono font-semibold text-gray-900">{{ $viewingVariant->barcode }}</p>
                    </div>

                    <div class="flex justify-between items-center mt-6 pt-4 border-t">
                        <div class="text-sm text-gray-600">
                            <p><strong>SKU:</strong> {{ $viewingVariant->sku }}</p>
                            <p><strong>Precio:</strong> ${{ number_format($viewingVariant->price, 2) }}</p>
                        </div>
                        <a href="data:image/svg+xml;base64,{{ base64_encode($this->generateBarcodeSvg($viewingVariant->barcode)) }}"
                            download="barcode-{{ $viewingVariant->barcode }}.svg"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Descargar SVG
                        </a>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-6">
                    ¿Estás seguro de que deseas eliminar esta variante? Esta acción no se puede deshacer.
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelDelete"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
