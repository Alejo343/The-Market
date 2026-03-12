<?php
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ProductService;
use App\Services\MediaService;
use App\Services\CategoryService;
use App\Services\BrandService;
use App\Services\RegionService;

new class extends Component {
    use WithFileUploads;
    public string $name = '';
    public string $description = '';
    public string $sale_type = 'unit';
    public ?int $category_id = null;
    public ?int $brand_id = null;
    public ?int $region_id = null;
    public bool $active = true;

    // Propiedades para imágenes
    public array $images = [];
    public array $imageOrder = [];

    /**
     * Obtiene categorías y marcas para los selectores
     */
    public function with(CategoryService $categoryService, BrandService $brandService, RegionService $regionService): array
    {
        return [
            'categories' => $categoryService->getAll(),
            'brands' => $brandService->getAll(),
            'regions' => $regionService->getActive(),
        ];
    }

    /**
     * Crea un nuevo producto
     */
    public function save(ProductService $productService, MediaService $mediaService)
    {
        $validated = $this->validate(
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sale_type' => 'required|in:unit,weight',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'region_id' => 'nullable|exists:regions,id',
                'active' => 'boolean',
                'images.*' => 'nullable|image|max:2048',
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
                'images.*.image' => 'El archivo debe ser una imagen',
                'images.*.max' => 'Cada imagen no puede exceder 2MB',
            ],
        );

        try {
            // Crear el producto
            $product = $productService->create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'sale_type' => $validated['sale_type'],
                'category_id' => $validated['category_id'],
                'brand_id' => $validated['brand_id'],
                'region_id' => $validated['region_id'],
                'active' => $validated['active'],
            ]);

            // Subir imágenes en el orden definido por el usuario
            if (!empty($this->images)) {
                $orderedImages = !empty($this->imageOrder) ? array_map(fn($i) => $this->images[$i], $this->imageOrder) : array_values($this->images);

                $mediaService->uploadMultipleProductImages($product, $orderedImages, null, true);
            }

            session()->flash('success', 'Producto creado exitosamente con ' . count($this->images) . ' imagen(es)');
            return $this->redirect('/products');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el producto: ' . $e->getMessage());
        }
    }

    public function removeImage(int $orderPosition): void
    {
        $realIndex = $this->imageOrder[$orderPosition] ?? null;
        if ($realIndex === null) {
            return;
        }

        array_splice($this->imageOrder, $orderPosition, 1);

        $this->imageOrder = array_values(array_map(fn($i) => $i > $realIndex ? $i - 1 : $i, $this->imageOrder));

        $images = $this->images;
        array_splice($images, $realIndex, 1);
        $this->images = array_values($images);
    }

    public function reorderImages(array $newOrder): void
    {
        $this->imageOrder = array_map('intval', $newOrder);
    }

    public function updatedImages(): void
    {
        $this->imageOrder = array_keys($this->images);
    }

    /**
     * Cancela y regresa al listado
     */
    public function cancel()
    {
        return $this->redirect('/products');
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nuevo Producto</h1>
        <p class="text-gray-600">Crea un nuevo producto en el catálogo</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md p-6 max-w-3xl">
        <form wire:submit="save">
            <!-- Name Field -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre <span class="text-red-600">*</span>
                </label>
                <input type="text" id="name" wire:model="name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    placeholder="Ingresa el nombre del producto">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description Field -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Descripción
                </label>
                <textarea id="description" wire:model="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                    placeholder="Descripción del producto (opcional)"></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Category Field -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Categoría <span class="text-red-600">*</span>
                    </label>
                    <select id="category_id" wire:model="category_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('category_id') border-red-500 @enderror">
                        <option value="">Seleccionar categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->parent ? '└─ ' : '' }}{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Brand Field -->
                <div>
                    <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Marca
                    </label>
                    <select id="brand_id" wire:model="brand_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('brand_id') border-red-500 @enderror">
                        <option value="">Sin marca</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    @error('brand_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Region Field -->
                <div>
                    <label for="region_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Región
                    </label>
                    <select id="region_id" wire:model="region_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('region_id') border-red-500 @enderror">
                        <option value="">Sin región</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                    @error('region_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Sale Type Field -->
            <div class="mb-6">
                <label for="sale_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo de Venta <span class="text-red-600">*</span>
                </label>
                <select id="sale_type" wire:model="sale_type"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('sale_type') border-red-500 @enderror">
                    <option value="unit">Unidad</option>
                    <option value="weight">Peso</option>
                </select>
                @error('sale_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Selecciona "Unidad" para productos que se venden por piezas (ej. latas, botellas) o "Peso" para
                    productos que se venden por kilogramo (ej. carnes, frutas)
                </p>
            </div>

            <!-- Active Field -->
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="active"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Producto activo</span>
                </label>
                <p class="mt-1 text-sm text-gray-500 ml-6">
                    Los productos inactivos no aparecerán en el punto de venta
                </p>
            </div>

            <!-- Images Upload Field -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Imágenes del Producto
                </label>

                {{-- Zona de drop / selector --}}
                <label for="images-input"
                    class="flex flex-col items-center justify-center gap-2 w-full px-6 py-8 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:border-blue-400 hover:bg-blue-50 transition-colors @error('images.*') border-red-400 @enderror">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Arrastra imágenes aquí</span>
                    <span class="text-xs text-gray-400">o haz clic para seleccionar archivos</span>
                    <span class="mt-1 text-xs text-gray-400 bg-white border border-gray-200 rounded-md px-3 py-1">
                        PNG, JPG, WEBP — Máx. 2MB por imagen
                    </span>
                    <input id="images-input" type="file" wire:model="images" multiple accept="image/*"
                        class="hidden">
                </label>

                @error('images.*')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

                {{-- Previews con drag & drop --}}
                @if ($images)
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3" id="images-sortable" x-data="imagesSorter()"
                        x-init="init()">
                        @foreach ($imageOrder as $position => $realIndex)
                            @php $image = $images[$realIndex]; @endphp
                            <div class="relative group cursor-grab active:cursor-grabbing rounded-lg overflow-hidden"
                                style="aspect-ratio: 1;" wire:key="preview-{{ $realIndex }}"
                                data-index="{{ $realIndex }}">
                                <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover">

                                {{-- Borde azul si es principal --}}
                                <div
                                    class="absolute inset-0 rounded-lg pointer-events-none
                                    {{ $position === 0 ? 'ring-2 ring-blue-500' : 'ring-1 ring-black/10' }}">
                                </div>

                                {{-- Badge principal --}}
                                @if ($position === 0)
                                    <span
                                        class="absolute top-1.5 left-1.5 bg-blue-600 text-white text-xs font-medium px-2 py-0.5 rounded">
                                        Principal
                                    </span>
                                @endif

                                {{-- Botón eliminar --}}
                                <button type="button" wire:click="removeImage({{ $position }})"
                                    class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-700 hover:bg-red-800 flex items-center justify-center transition-colors shadow"
                                    title="Eliminar">
                                    <svg class="w-3 h-3 text-red-100" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                {{-- Hint arrastre --}}
                                <div
                                    class="absolute bottom-0 inset-x-0 bg-black/40 py-1 flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20" />
                                    </svg>
                                    <span class="text-xs text-white">Arrastra</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
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
                    Crear Producto
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        function imagesSorter() {
            return {
                init() {
                    this.$nextTick(() => {
                        const el = document.getElementById('images-sortable');
                        if (!el) return;
                        Sortable.create(el, {
                            animation: 150,
                            ghostClass: 'opacity-40',
                            onEnd: () => {
                                const newOrder = [...el.querySelectorAll('[data-index]')]
                                    .map(el => parseInt(el.dataset.index));
                                @this.reorderImages(newOrder);
                            }
                        });
                    });
                }
            }
        }
    </script>

    <!-- Help Text -->
    <div class="mt-6 max-w-3xl bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Siguiente paso:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Después de crear el producto, podrás agregar variantes (para productos por unidad) o lotes de
                        peso (para productos por peso)</li>
                    <li>Las variantes incluyen información como presentación, precio y código de barras</li>
                    <li>Los lotes de peso incluyen precio por kilogramo y stock disponible</li>
                </ul>
            </div>
        </div>
    </div>
</div>
