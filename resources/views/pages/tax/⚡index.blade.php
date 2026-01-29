<?php
use Livewire\Component;
use App\Services\TaxService;
use App\Models\Tax;

new class extends Component {
    public string $search = '';
    public bool $showActiveOnly = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Form fields para edición
    public string $name = '';
    public string $percentage = '';
    public bool $active = true;

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener los impuestos filtrados
     */
    public function with(TaxService $taxService): array
    {
        try {
            $taxes = $taxService->list(activeOnly: $this->showActiveOnly, search: $this->search);

            $taxes->loadCount(['productVariants']);

            return [
                'taxes' => $taxes,
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = $this->translateError($e->getMessage());
            return [
                'taxes' => collect(),
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

    public function updatedShowActiveOnly()
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
            $tax = Tax::findOrFail($id);
            $this->name = $tax->name;
            $this->percentage = $tax->percentage;
            $this->active = $tax->active;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cargar el impuesto';
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
        $this->percentage = '';
        $this->active = true;
    }

    /**
     * Guarda el impuesto editado
     */
    public function save(TaxService $taxService)
    {
        $this->resetMessages();

        $this->validate(
            [
                'name' => 'required|string|max:50',
                'percentage' => 'required|numeric|min:0|max:100',
                'active' => 'boolean',
            ],
            [
                'name.required' => 'El nombre es obligatorio',
                'name.max' => 'El nombre no puede exceder 50 caracteres',
                'percentage.required' => 'El porcentaje es obligatorio',
                'percentage.numeric' => 'El porcentaje debe ser un número',
                'percentage.min' => 'El porcentaje debe ser mayor o igual a 0',
                'percentage.max' => 'El porcentaje no puede ser mayor a 100',
            ],
        );

        try {
            $tax = Tax::findOrFail($this->editingId);

            $taxService->update($tax, [
                'name' => $this->name,
                'percentage' => $this->percentage,
                'active' => $this->active,
            ]);

            $this->successMessage = 'Impuesto actualizado exitosamente';
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
     * Elimina el impuesto
     */
    public function delete(TaxService $taxService)
    {
        $this->resetMessages();

        try {
            $tax = Tax::findOrFail($this->deletingId);
            $taxService->delete($tax);

            $this->successMessage = 'Impuesto eliminado exitosamente';
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
            'TAX_IN_USE' => 'No se puede eliminar: el impuesto está siendo utilizado en variantes de productos',
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Impuestos</h1>
        <p class="text-gray-600">Gestiona los impuestos aplicables a los productos</p>
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
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar impuestos..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Filter Active Only -->
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="showActiveOnly"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm text-gray-700">Solo impuestos activos</span>
            </label>
        </div>

        <!-- Add Button -->
        <a href="/taxes/create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Impuesto
        </a>
    </div>

    <!-- Taxes Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Porcentaje
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Productos
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
                @forelse($taxes as $tax)
                    <tr class="hover:bg-gray-50 transition-colors" wire:key="tax-{{ $tax->id }}">
                        <td class="px-6 py-4">
                            @if ($editingId === $tax->id)
                                <input type="text" wire:model="name"
                                    class="w-full px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('name')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            @else
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $tax->name }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($editingId === $tax->id)
                                <div class="flex items-center gap-1">
                                    <input type="number" step="0.01" wire:model="percentage"
                                        class="w-24 px-3 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <span class="text-sm text-gray-600">%</span>
                                </div>
                                @error('percentage')
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            @else
                                <span class="text-sm text-gray-600">
                                    {{ number_format($tax->percentage, 2) }}%
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">
                                {{ $tax->product_variants_count ?? 0 }} variantes
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($editingId === $tax->id)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="active"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Activo</span>
                                </label>
                            @else
                                <span
                                    class="px-2 py-1 text-xs rounded-full {{ $tax->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $tax->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if ($editingId === $tax->id)
                                <div class="flex justify-end gap-2" wire:key="edit-buttons-{{ $tax->id }}">
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
                                <div class="flex justify-end gap-2" wire:key="action-buttons-{{ $tax->id }}">
                                    <button wire:click="edit({{ $tax->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $tax->id }})"
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
                                No se encontraron impuestos que coincidan con "{{ $search }}"
                            @else
                                No hay impuestos registrados
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
                    ¿Estás seguro de que deseas eliminar este impuesto? Esta acción no se puede deshacer.
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
