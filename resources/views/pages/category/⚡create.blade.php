<?php
use Livewire\Component;
use App\Services\CategoryService;

new class extends Component {
    public string $name = '';
    public ?int $parent_id = null;

    /**
     * Computed property para obtener todas las categorías disponibles
     */
    public function with(CategoryService $categoryService): array
    {
        return [
            'categories' => $categoryService->getAll(),
        ];
    }

    /**
     * Crea una nueva categoría
     */
    public function save(CategoryService $categoryService)
    {
        $validated = $this->validate(
            [
                'name' => 'required|string|max:100|unique:categories,name',
                'parent_id' => 'nullable|exists:categories,id',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 100 caracteres',
                'name.unique' => 'Ya existe una categoría con este nombre',
                'parent_id.exists' => 'La categoría padre no existe',
            ],
        );

        try {
            $categoryService->create($validated);

            session()->flash('success', 'Categoría creada exitosamente');
            return $this->redirect('/categories');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la categoría: ' . $e->getMessage());
        }
    }

    /**
     * Cancela y regresa al listado
     */
    public function cancel()
    {
        return $this->redirect('/categories');
    }
};
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Nueva Categoría</h1>
        <p class="text-gray-600">Crea una nueva categoría de productos</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl">
        <form wire:submit="save">
            <!-- Name Field -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre <span class="text-red-600">*</span>
                </label>
                <input type="text" id="name" wire:model="name"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror"
                    placeholder="Ingresa el nombre de la categoría">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Parent Category Field -->
            <div class="mb-6">
                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Categoría Padre (Opcional)
                </label>
                <select id="parent_id" wire:model="parent_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('parent_id') border-red-500 @enderror">
                    <option value="">Sin categoría padre</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->parent ? '└─ ' : '' }}{{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    Si seleccionas una categoría padre, esta será una subcategoría
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
                    Crear Categoría
                </button>
            </div>
        </form>
    </div>

    <!-- Help Text -->
    <div class="mt-6 max-w-2xl bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">Información útil:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>El nombre de la categoría debe ser único</li>
                    <li>Puedes crear categorías raíz o subcategorías</li>
                    <li>Las subcategorías heredan la estructura de su categoría padre</li>
                </ul>
            </div>
        </div>
    </div>
</div>
