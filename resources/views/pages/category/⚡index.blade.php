<?php
use Livewire\Component;
use App\Services\CategoryService;
use App\Models\Category;

new class extends Component {
    public string $search = '';
    public bool $showRootOnly = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Form fields para edición
    public string $name = '';
    public ?int $parent_id = null;

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener las categorías filtradas
     */
    public function with(CategoryService $categoryService): array
    {
        try {
            if ($this->showRootOnly) {
                $categories = $categoryService->getRootCategories();
            } elseif ($this->search) {
                $categories = $categoryService->search($this->search);
            } else {
                $categories = $categoryService->getAll();
            }

            $categories->loadCount(['subcategories', 'products']);

            return [
                'categories' => $categories,
                'allCategories' => $categoryService->getAll(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = $this->translateError($e->getMessage());
            return [
                'categories' => collect(),
                'allCategories' => collect(),
            ];
        }
    }

    /**
     * Actualiza el filtro de búsqueda
     */
    public function updatedSearch()
    {
        $this->resetMessages();
    }

    /**
     * Actualiza el filtro de solo raíz
     */
    public function updatedShowRootOnly()
    {
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
            $category = Category::findOrFail($id);
            $this->name = $category->name;
            $this->parent_id = $category->parent_id;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cargar la categoría';
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
        $this->parent_id = null;
    }

    /**
     * Guarda la categoría editada
     */
    public function save(CategoryService $categoryService)
    {
        \Log::info('Save method called!');
        $this->resetMessages();

        $this->validate(
            [
                'name' => 'required|string|max:100',
                'parent_id' => 'nullable|exists:categories,id',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 100 caracteres',
                'parent_id.exists' => 'La categoría padre no existe',
            ],
        );

        try {
            $category = Category::findOrFail($this->editingId);

            // Validar que no se establezca como su propio padre
            if ($this->parent_id == $this->editingId) {
                $this->errorMessage = 'Una categoría no puede ser su propio padre';
                return;
            }

            $categoryService->update($category, [
                'name' => $this->name,
                'parent_id' => $this->parent_id,
            ]);

            $this->successMessage = 'Categoría actualizada exitosamente';
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
     * Elimina la categoría
     */
    public function delete(CategoryService $categoryService)
    {
        $this->resetMessages();

        try {
            $category = Category::findOrFail($this->deletingId);
            $categoryService->delete($category);

            $this->successMessage = 'Categoría eliminada exitosamente';
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
            'CATEGORY_HAS_SUBCATEGORIES' => 'No se puede eliminar: la categoría tiene subcategorías',
            'CATEGORY_HAS_PRODUCTS' => 'No se puede eliminar: la categoría tiene productos asociados',
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Categorías</h1>
        <p class="text-gray-600">Gestiona las categorías de productos</p>
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

    <!-- Filters and Actions -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="flex-1 max-w-md">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar categorías..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Filter Root Only -->
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="showRootOnly"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm text-gray-700">Solo categorías raíz</span>
            </label>
        </div>

        <!-- Add Button -->
        <a href="/categories/create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nueva Categoría
        </a>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Categoría Padre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Subcategorías
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Productos
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($categories as $category)
                    <tr class="hover:bg-gray-50 transition-colors" wire:key="category-{{ $category->id }}">
                        <td class="px-6 py-4">
                            @if ($editingId === $category->id)
                                <input type="text" wire:model="name"
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('name')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            @else
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $category->name }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($editingId === $category->id)
                                <select wire:model="parent_id"
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Sin categoría padre</option>
                                    @foreach ($allCategories as $cat)
                                        @if ($cat->id !== $category->id)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            @else
                                <span class="text-sm text-gray-600">
                                    {{ $category->parent?->name ?? '-' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">
                                {{ $category->subcategories_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">
                                {{ $category->products_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if ($editingId === $category->id)
                                <div class="flex justify-end gap-2" wire:key="edit-buttons-{{ $category->id }}">
                                    <button wire:click="save"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors">
                                        Guardar
                                    </button>
                                    <button wire:click="cancelEdit"
                                        class="px-3 py-1 bg-gray-300 text-gray-700 text-sm rounded hover:bg-gray-400 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            @else
                                <div class="flex justify-end gap-2" wire:key="action-buttons-{{ $category->id }}">
                                    <button wire:click="edit({{ $category->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $category->id }})"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded transition-colors"
                                        title="Eliminar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            @if ($search)
                                No se encontraron categorías que coincidan con "{{ $search }}"
                            @else
                                No hay categorías registradas
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Delete Confirmation Modal -->
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-6">
                    ¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer.
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
