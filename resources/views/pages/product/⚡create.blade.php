<?php
use Livewire\Component;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\BrandService;

new class extends Component {
    public string $name = '';
    public string $description = '';
    public string $sale_type = 'unit';
    public ?int $category_id = null;
    public ?int $brand_id = null;
    public bool $active = true;

    /**
     * Obtiene categorías y marcas para los selectores
     */
    public function with(CategoryService $categoryService, BrandService $brandService): array
    {
        return [
            'categories' => $categoryService->getAll(),
            'brands' => $brandService->getAll(),
        ];
    }

    /**
     * Crea un nuevo producto
     */
    public function save(ProductService $productService)
    {
        $validated = $this->validate(
            [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sale_type' => 'required|in:unit,weight',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'active' => 'boolean',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 255 caracteres',
                'sale_type.required' => 'El tipo de venta es obligatorio',
                'sale_type.in' => 'El tipo de venta debe ser unidad o peso',
                'category_id.required' => 'La categoría es obligatoria',
                'category_id.exists' => 'La categoría seleccionada no existe',
                'brand_id.exists' => 'La marca seleccionada no existe',
            ],
        );

        try {
            $productService->create($validated);

            session()->flash('success', 'Producto creado exitosamente');
            return $this->redirect('/products');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el producto: ' . $e->getMessage());
        }
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

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" wire:click="cancel"
                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Crear Producto
                </button>
            </div>
        </form>
    </div>

    <!-- Help Text -->
    <div class="mt-6 max-w-3xl bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
