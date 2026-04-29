<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\BrandService;
use App\Services\RegionService;
use App\Services\MediaService;
use App\Models\Product;

new class extends Component {
    use WithPagination, WithFileUploads;

    public string $search = '';
    public ?int $filterCategoryId = null;
    public ?int $filterBrandId = null;
    public ?int $filterRegionId = null;
    public ?string $filterSaleType = null;
    public ?string $filterStatus = null;
    public int $perPage = 15;
    public string $sortDirection = 'asc';

    public bool $filterNoBrand = false;
    public bool $filterNoCategory = false;
    public bool $filterNoRegion = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Form fields para edición
    public string $name = '';
    public string $description = '';
    public string $sale_type = 'unit';
    public ?int $category_id = null;
    public int|string|null $brand_id = null;
    public string $brandInput = '';
    public ?int $region_id = null;
    public bool $active = true;

    // Imágenes existentes del producto
    public array $existingMedia = [];
    // Nuevas imágenes a subir
    public array $newImages = [];

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener los productos filtrados
     */
    public function with(ProductService $productService, CategoryService $categoryService, BrandService $brandService, RegionService $regionService): array
    {
        try {
            $products = $productService->list(categoryId: $this->filterCategoryId, brandId: $this->filterBrandId, regionId: $this->filterRegionId, saleType: $this->filterSaleType, status: $this->filterStatus, noBrand: $this->filterNoBrand, noCategory: $this->filterNoCategory, noRegion: $this->filterNoRegion, search: $this->search, include: ['category', 'brand', 'region', 'media'], perPage: $this->perPage, sortDirection: $this->sortDirection);
            $products->getCollection()->loadCount(['variants', 'weightLots', 'media']);

            return [
                'products' => $products,
                'categories' => $categoryService->getAll(),
                'brands' => $brandService->getAll(),
                'regions' => $regionService->getActive(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = $this->translateError($e->getMessage());
            return [
                'products' => collect(),
                'categories' => collect(),
                'brands' => collect(),
                'regions' => collect(),
            ];
        }
    }

    public function startNewBrand(): void
    {
        $this->brand_id = -1;
        $this->brandInput = '';
    }

    public function cancelNewBrand(): void
    {
        $this->brand_id = null;
        $this->brandInput = '';
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

    public function updatedFilterBrandId()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedFilterRegionId()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedFilterSaleType()
    {
        $this->resetPage();
        $this->resetMessages();
    }

    public function updatedFilterStatus()
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

    public function toggleActive(int $productId)
    {
        $product = Product::findOrFail($productId);
        $product->update(['active' => !$product->active]);
    }

    public function updatedFilterNoBrand()
    {
        $this->resetPage();
    }
    public function updatedFilterNoCategory()
    {
        $this->resetPage();
    }
    public function updatedFilterNoRegion()
    {
        $this->resetPage();
    }

    /**
     * Limpia todos los filtros
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->filterCategoryId = null;
        $this->filterBrandId = null;
        $this->filterRegionId = null;
        $this->filterSaleType = null;
        $this->filterStatus = null;
        $this->filterNoBrand = false;
        $this->filterNoCategory = false;
        $this->filterNoRegion = false;
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
        $this->newImages = [];

        try {
            $product = Product::with('media')->findOrFail($id);
            $this->name = $product->name;
            $this->description = $product->description ?? '';
            $this->sale_type = $product->sale_type;
            $this->category_id = $product->category_id;
            $this->brand_id = $this->brandInput = '';
            $this->region_id = $product->region_id;
            $this->active = $product->active;

            // Cargar imágenes existentes como array serializable
            $this->existingMedia = $product->media
                ->map(
                    fn($m) => [
                        'id' => $m->id,
                        'url' => $m->url,
                        'filename' => $m->filename,
                        'is_primary' => (bool) $m->pivot->is_primary,
                    ],
                )
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cargar el producto';
            $this->cancelEdit();
        }
    }

    /**
     * Cancela la edición
     */
    public function cancelEdit()
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->sale_type = 'unit';
        $this->category_id = null;
        $this->brand_id = null;
        $this->region_id = null;
        $this->brandInput = '';
        $this->active = true;
        $this->existingMedia = [];
        $this->newImages = [];
    }

    /**
     * Elimina una imagen existente del producto
     */
    public function removeExistingMedia(int $mediaId, MediaService $mediaService)
    {
        try {
            $product = Product::findOrFail($this->editingId);
            $media = \App\Models\Media::findOrFail($mediaId);
            $mediaService->deleteProductImage($product, $media);

            $this->existingMedia = collect($this->existingMedia)->reject(fn($m) => $m['id'] === $mediaId)->values()->toArray();

            // Si se eliminó la principal y quedan imágenes, promover la primera
            $hasPrimary = collect($this->existingMedia)->contains('is_primary', true);
            if (!$hasPrimary && count($this->existingMedia) > 0) {
                $this->setPrimaryMedia($this->existingMedia[0]['id'], $mediaService);
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar la imagen';
        }
    }

    /**
     * Establece una imagen existente como principal
     */
    public function setPrimaryMedia(int $mediaId, MediaService $mediaService)
    {
        try {
            $product = Product::findOrFail($this->editingId);
            $media = \App\Models\Media::findOrFail($mediaId);
            $mediaService->setPrimaryImage($product, $media);

            $this->existingMedia = collect($this->existingMedia)
                ->map(function ($m) use ($mediaId) {
                    $m['is_primary'] = $m['id'] === $mediaId;
                    return $m;
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cambiar la imagen principal';
        }
    }

    /**
     * Elimina una nueva imagen (aún no subida) de la cola
     */
    public function removeNewImage(int $index)
    {
        $images = $this->newImages;
        array_splice($images, $index, 1);
        $this->newImages = array_values($images);
    }

    /**
     * Guarda el producto editado
     */
    public function save(ProductService $productService, MediaService $mediaService)
    {
        $this->resetMessages();

        $this->validate(
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sale_type' => 'required|in:unit,weight',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'nullable|integer',
                'brandInput' => 'nullable|string|max:255',
                'region_id' => 'nullable|exists:regions,id',
                'active' => 'boolean',
                'newImages.*' => 'nullable|image|max:2048',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 255 caracteres',
                'sale_type.required' => 'El tipo de venta es obligatorio',
                'sale_type.in' => 'El tipo de venta debe ser unidad o peso',
                'category_id.required' => 'La categoría es obligatoria',
                'category_id.exists' => 'La categoría seleccionada no existe',
                'brand_id.exists' => 'La marca seleccionada no existe',
                'region_id.exists' => 'La región seleccionada no existe',
                'newImages.*.image' => 'El archivo debe ser una imagen',
                'newImages.*.max' => 'Cada imagen no puede exceder 2MB',
            ],
        );

        try {
            $product = Product::findOrFail($this->editingId);

            // Resolver marca
            $brandId = null;
            if ((int) $this->brand_id === -1 && trim($this->brandInput) !== '') {
                $brand = app(BrandService::class)->create(['name' => trim($this->brandInput)]);
                $brandId = $brand->id;
            } elseif ((int) $this->brand_id > 0) {
                $brandId = $this->brand_id;
            }

            $productService->update($product, [
                'name' => $this->name,
                'description' => $this->description,
                'sale_type' => $this->sale_type,
                'category_id' => $this->category_id,
                'brand_id' => $brandId,
                'region_id' => $this->region_id,
                'active' => $this->active,
            ]);

            // Subir nuevas imágenes si las hay
            if (!empty($this->newImages)) {
                $noPrimaryYet = collect($this->existingMedia)->doesntContain('is_primary', true);
                $mediaService->uploadMultipleProductImages($product, array_values($this->newImages), null, $noPrimaryYet);
            }

            $this->successMessage = 'Producto actualizado exitosamente';
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
     * Elimina el producto
     */
    public function delete(ProductService $productService)
    {
        $this->resetMessages();

        try {
            $product = Product::findOrFail($this->deletingId);
            $productService->delete($product);

            $this->successMessage = 'Producto eliminado exitosamente';
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
     * Traduce los mensajes de error al español
     */
    private function translateError(string $error): string
    {
        return match ($error) {
            'PRODUCT_HAS_VARIANTS' => 'No se puede eliminar: el producto tiene variantes asociadas',
            'PRODUCT_HAS_WEIGHT_LOTS' => 'No se puede eliminar: el producto tiene lotes de peso asociados',
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Productos</h1>
        <p class="text-gray-600">Gestiona el catálogo de productos</p>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar productos..."
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

            <!-- Brand Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                <select wire:model.live="filterBrandId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todas las marcas</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
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

            <!-- Sale Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Venta</label>
                <select wire:model.live="filterSaleType"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los tipos</option>
                    <option value="unit">Unidad</option>
                    <option value="weight">Peso</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6 flex-wrap">
                <select wire:model.live="filterStatus"
                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="filterNoCategory"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Sin categoría</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="filterNoBrand"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Sin marca</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="filterNoRegion"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Sin región</span>
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
        <a href="/products/create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Producto
        </a>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Imagen
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="toggleSort" class="inline-flex items-center gap-1 hover:text-gray-900 transition-colors">
                                Nombre
                                @if ($sortDirection === 'asc')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Categoría
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Marca
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Región
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Variantes/Lotes
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="product-{{ $product->id }}">
                            <td class="px-6 py-4">
                                @php
                                    $primaryImage = $product->media->where('pivot.is_primary', true)->first();
                                @endphp
                                @if ($primaryImage)
                                    <img src="{{ $primaryImage->url }}" alt="{{ $product->name }}"
                                        class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                @else
                                    <div
                                        class="w-16 h-16 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                @endif
                                @if ($product->media_count > 1)
                                    <div class="text-xs text-gray-500 mt-1">+{{ $product->media_count - 1 }} más</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $product->name }}
                                </div>
                                @if ($product->description)
                                    <div class="text-xs text-gray-500">{{ Str::limit($product->description, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $product->category?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $product->brand?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $product->region?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full {{ $product->sale_type === 'unit' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $product->sale_type === 'unit' ? 'Unidad' : 'Peso' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleActive({{ $product->id }})"
                                    title="Clic para {{ $product->active ? 'desactivar' : 'activar' }} el producto"
                                    class="px-2 py-1 text-xs rounded-full transition-colors {{ $product->active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                    {{ $product->active ? 'Activo' : 'Inactivo' }}
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">
                                    {{ $product->sale_type === 'unit' ? ($product->variants_count ?? 0) . ' variantes' : ($product->weight_lots_count ?? 0) . ' lotes' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2" wire:key="action-buttons-{{ $product->id }}">
                                    @if ($product->sale_type === 'unit')
                                        <a href="{{ route('product-variants.create', ['product_id' => $product->id]) }}"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded transition-colors"
                                            title="Agregar variante">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                        </a>
                                    @endif
                                    <button wire:click="edit({{ $product->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $product->id }})"
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
                            <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                @if ($search || $filterCategoryId || $filterBrandId || $filterSaleType)
                                    No se encontraron productos con los filtros aplicados
                                @else
                                    No hay productos registrados
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between gap-4">
                <!-- Contador -->
                <div class="text-sm text-gray-500">
                    @if ($products->total() > 0)
                        Mostrando <span class="font-medium text-gray-700">{{ $products->firstItem() }}</span>–<span
                            class="font-medium text-gray-700">{{ $products->lastItem() }}</span>
                        de <span class="font-medium text-gray-700">{{ $products->total() }}</span> productos
                    @else
                        Sin resultados
                    @endif
                </div>

                <!-- Botones de página -->
                @if ($products->hasPages())
                    <div class="flex items-center gap-1">
                        {{-- Anterior --}}
                        @if ($products->onFirstPage())
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

                        {{-- Páginas --}}
                        @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                            @if ($page == $products->currentPage())
                                <span
                                    class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg select-none">
                                    {{ $page }}
                                </span>
                            @elseif ($page == 1 || $page == $products->lastPage() || abs($page - $products->currentPage()) <= 2)
                                <button wire:click="gotoPage({{ $page }})"
                                    class="px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                    {{ $page }}
                                </button>
                            @elseif (abs($page - $products->currentPage()) == 3)
                                <span class="px-2 py-1.5 text-sm text-gray-400 select-none">…</span>
                            @endif
                        @endforeach

                        {{-- Siguiente --}}
                        @if ($products->hasMorePages())
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
        <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[92vh] flex flex-col">

                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">Editar Producto</h3>
                    <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (scrollable) -->
                <div class="overflow-y-auto px-6 py-5 space-y-5 flex-1">

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-400 @enderror">
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea wire:model="description" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none @error('description') border-red-400 @enderror"></textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Grid 2 col -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="category_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category_id') border-red-400 @enderror">
                                <option value="">Seleccionar categoría</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->parent ? '└─ ' : '' }}{{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Marca -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>

                            @if ($brand_id === -1)
                                <div class="flex gap-2">
                                    <input type="text" wire:model="brandInput"
                                        placeholder="Nombre de la nueva marca..."
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('brandInput') border-red-400 @enderror"
                                        autofocus>
                                    <button type="button" wire:click="cancelNewBrand"
                                        class="px-3 py-2 text-xs bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-green-600">Se creará una nueva marca al guardar</p>
                            @else
                                <div class="flex gap-2">
                                    <select wire:model="brand_id"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('brand_id') border-red-400 @enderror">
                                        <option value="">Sin marca</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="startNewBrand"
                                        class="px-3 py-2 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors whitespace-nowrap">
                                        + Nueva
                                    </button>
                                </div>
                            @endif

                            @error('brandInput')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            @error('brand_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Región -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Región</label>
                            <select wire:model="region_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('region_id') border-red-400 @enderror">
                                <option value="">Sin región</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tipo de Venta -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de Venta <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="sale_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('sale_type') border-red-400 @enderror">
                                <option value="unit">Unidad</option>
                                <option value="weight">Peso</option>
                            </select>
                            @error('sale_type')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer w-fit">
                            <input type="checkbox" wire:model="active"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Producto activo</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500 ml-6">Los productos inactivos no aparecen en el punto de
                            venta</p>
                    </div>

                    <!-- Imágenes existentes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Imágenes actuales
                            <span class="text-xs font-normal text-gray-400 ml-1">(haz clic en una para hacerla
                                principal)</span>
                        </label>

                        @if (count($existingMedia) > 0)
                            <div class="grid gap-3" style="grid-template-columns: repeat(2, 1fr);">
                                @foreach ($existingMedia as $media)
                                    <div class="relative group" wire:key="existing-{{ $media['id'] }}"
                                        style="aspect-ratio:1;">
                                        <img src="{{ $media['url'] }}" alt=""
                                            class="w-full h-full object-cover rounded-lg border-2 transition-all cursor-pointer
                                                   {{ $media['is_primary'] ? 'border-blue-500 ring-2 ring-blue-300' : 'border-gray-200 hover:border-blue-300' }}"
                                            wire:click="setPrimaryMedia({{ $media['id'] }})"
                                            title="{{ $media['is_primary'] ? 'Imagen principal' : 'Establecer como principal' }}">

                                        {{-- Badge principal --}}
                                        @if ($media['is_primary'])
                                            <span
                                                class="absolute top-1.5 left-1.5 bg-blue-600 text-white text-xs font-medium px-1.5 py-0.5 rounded shadow">
                                                Principal
                                            </span>
                                        @endif

                                        {{-- Botón eliminar --}}
                                        <button type="button" wire:click="removeExistingMedia({{ $media['id'] }})"
                                            wire:confirm="¿Eliminar esta imagen?"
                                            class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-600 hover:bg-red-700 flex items-center justify-center shadow opacity-0 group-hover:opacity-100 transition-opacity"
                                            title="Eliminar">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic">Sin imágenes cargadas</p>
                        @endif
                    </div>

                    <!-- Agregar nuevas imágenes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Agregar imágenes</label>

                        <label for="new-images-input"
                            class="flex flex-col items-center justify-center gap-1.5 w-full px-4 py-5 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:border-blue-400 hover:bg-blue-50 transition-colors">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" />
                            </svg>
                            <span class="text-sm text-gray-600 font-medium">Arrastra o selecciona imágenes</span>
                            <span class="text-xs text-gray-400">PNG, JPG, WEBP · Máx. 2MB por imagen</span>
                            <input id="new-images-input" type="file" wire:model="newImages" multiple
                                accept="image/*" class="hidden">
                        </label>

                        @error('newImages.*')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        {{-- Preview nuevas imágenes --}}
                        @if (count($newImages) > 0)
                            <div class="mt-3 grid grid-cols-3 sm:grid-cols-4 gap-3">
                                @foreach ($newImages as $i => $img)
                                    <div class="relative group" style="aspect-ratio:1;"
                                        wire:key="new-img-{{ $i }}">
                                        <img src="{{ $img->temporaryUrl() }}"
                                            class="w-full h-full object-cover rounded-lg border border-gray-200">
                                        <span
                                            class="absolute top-1.5 left-1.5 bg-gray-700/80 text-white text-xs px-1.5 py-0.5 rounded">Nueva</span>
                                        <button type="button" wire:click="removeNewImage({{ $i }})"
                                            class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-600 hover:bg-red-700 flex items-center justify-center shadow opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 shrink-0">
                    <button wire:click="cancelEdit"
                        class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2 disabled:opacity-60">
                        <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-6">
                    ¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.
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
