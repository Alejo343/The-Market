<?php
use Livewire\Component;
use App\Services\WeightLotService;
use App\Services\ProductService;
use App\Models\WeightLot;

new class extends Component {
    public string $search = '';
    public ?int $filterProductId = null;
    public bool $showExpiredOnly = false;
    public bool $showExpiringSoonOnly = false;
    public bool $showAvailableOnly = false;
    public bool $showActiveOnly = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    // Form fields para edición
    public int $product_id = 0;
    public string $initial_weight = '';
    public string $available_weight = '';
    public string $price_per_kg = '';
    public string $expires_at = '';
    public bool $active = true;

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener los lotes filtrados
     */
    public function with(WeightLotService $lotService, ProductService $productService): array
    {
        try {
            $lots = $lotService->list(productId: $this->filterProductId, activeOnly: $this->showActiveOnly, availableOnly: $this->showAvailableOnly, expiredOnly: $this->showExpiredOnly, expiringSoon: $this->showExpiringSoonOnly, include: ['product']);

            return [
                'lots' => $lots,
                'products' => $productService->getBySaleType('weight'),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = $this->translateError($e->getMessage());
            return [
                'lots' => collect(),
                'products' => collect(),
            ];
        }
    }

    /**
     * Actualiza los filtros
     */
    public function updatedSearch()
    {
        $this->resetMessages();
    }

    public function updatedFilterProductId()
    {
        $this->resetMessages();
    }

    public function updatedShowExpiredOnly()
    {
        $this->resetMessages();
    }

    public function updatedShowExpiringSoonOnly()
    {
        $this->resetMessages();
    }

    public function updatedShowAvailableOnly()
    {
        $this->resetMessages();
    }

    public function updatedShowActiveOnly()
    {
        $this->resetMessages();
    }

    /**
     * Limpia todos los filtros
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->filterProductId = null;
        $this->showExpiredOnly = false;
        $this->showExpiringSoonOnly = false;
        $this->showAvailableOnly = false;
        $this->showActiveOnly = false;
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
            $lot = WeightLot::findOrFail($id);
            $this->product_id = $lot->product_id;
            $this->initial_weight = $lot->initial_weight;
            $this->available_weight = $lot->available_weight;
            $this->price_per_kg = $lot->price_per_kg;
            $this->expires_at = $lot->expires_at ? $lot->expires_at->format('Y-m-d') : '';
            $this->active = $lot->active;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cargar el lote';
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
        $this->initial_weight = '';
        $this->available_weight = '';
        $this->price_per_kg = '';
        $this->expires_at = '';
        $this->active = true;
    }

    /**
     * Guarda el lote editado
     */
    public function save(WeightLotService $lotService)
    {
        $this->resetMessages();

        $this->validate(
            [
                'product_id' => 'required|exists:products,id',
                'initial_weight' => 'required|numeric|min:0.001',
                'available_weight' => 'required|numeric|min:0|lte:initial_weight',
                'price_per_kg' => 'required|numeric|min:0',
                'expires_at' => 'nullable|date|after:today',
                'active' => 'boolean',
            ],
            [
                'product_id.required' => 'El producto es obligatorio',
                'product_id.exists' => 'El producto seleccionado no existe',
                'initial_weight.required' => 'El peso inicial es obligatorio',
                'initial_weight.numeric' => 'El peso inicial debe ser un número',
                'initial_weight.min' => 'El peso inicial debe ser mayor a 0',
                'available_weight.required' => 'El peso disponible es obligatorio',
                'available_weight.numeric' => 'El peso disponible debe ser un número',
                'available_weight.min' => 'El peso disponible debe ser mayor o igual a 0',
                'available_weight.lte' => 'El peso disponible no puede ser mayor al peso inicial',
                'price_per_kg.required' => 'El precio por kg es obligatorio',
                'price_per_kg.numeric' => 'El precio por kg debe ser un número',
                'price_per_kg.min' => 'El precio por kg debe ser mayor o igual a 0',
                'expires_at.date' => 'La fecha de vencimiento debe ser una fecha válida',
                'expires_at.after' => 'La fecha de vencimiento debe ser posterior a hoy',
            ],
        );

        try {
            $lot = WeightLot::findOrFail($this->editingId);

            $lotService->update($lot, [
                'product_id' => $this->product_id,
                'initial_weight' => $this->initial_weight,
                'available_weight' => $this->available_weight,
                'price_per_kg' => $this->price_per_kg,
                'expires_at' => $this->expires_at ?: null,
                'active' => $this->active,
            ]);

            $this->successMessage = 'Lote actualizado exitosamente';
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
     * Elimina el lote
     */
    public function delete(WeightLotService $lotService)
    {
        $this->resetMessages();

        try {
            $lot = WeightLot::findOrFail($this->deletingId);
            $lotService->delete($lot);

            $this->successMessage = 'Lote eliminado exitosamente';
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
            'WEIGHT_LOT_HAS_SALES' => 'No se puede eliminar: el lote tiene ventas asociadas',
            'WEIGHT_LOT_HAS_MOVEMENTS' => 'No se puede eliminar: el lote tiene movimientos de inventario asociados',
            'WEIGHT_LOT_INACTIVE' => 'El lote está inactivo',
            'WEIGHT_LOT_EXPIRED' => 'El lote está vencido',
            'INSUFFICIENT_WEIGHT' => 'No hay suficiente peso disponible',
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Lotes de Peso</h1>
        <p class="text-gray-600">Gestiona los lotes de productos vendidos por peso</p>
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Product Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                <select wire:model.live="filterProductId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los productos</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 items-center justify-between">
            <div class="flex flex-wrap gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showExpiredOnly"
                        class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                    <span class="text-sm text-gray-700">Vencidos</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showExpiringSoonOnly"
                        class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                    <span class="text-sm text-gray-700">Por vencer</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showAvailableOnly"
                        class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                    <span class="text-sm text-gray-700">Disponibles</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model.live="showActiveOnly"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Solo activos</span>
                </label>
            </div>

            <button wire:click="clearFilters"
                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                Limpiar filtros
            </button>
        </div>
    </div>

    <!-- Actions -->
    <div class="mb-6 flex justify-end">
        <a href="/weight-lots/create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Lote
        </a>
    </div>

    <!-- Lots Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Producto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Peso Inicial
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Disponible
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Precio/kg
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vencimiento
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
                    @forelse($lots as $lot)
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="lot-{{ $lot->id }}">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $lot->product->name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ number_format($lot->initial_weight, 3) }}
                                    kg</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ number_format($lot->available_weight, 3) }} kg
                                </div>
                                <div class="text-xs text-gray-500">
                                    Vendido: {{ number_format($lot->getSoldWeight(), 3) }} kg
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900">${{ number_format($lot->price_per_kg, 2) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($lot->expires_at)
                                    <div class="text-sm text-gray-900">{{ $lot->expires_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $lot->expires_at->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500">Sin vencimiento</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if (!$lot->active)
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                        Inactivo
                                    </span>
                                @elseif($lot->isExpired())
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                        Vencido
                                    </span>
                                @elseif($lot->expires_at && $lot->expires_at >= now() && $lot->expires_at <= now()->addDays(7))
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                        Por vencer
                                    </span>
                                @elseif($lot->available_weight <= 0)
                                    <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">
                                        Agotado
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        Disponible
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2" wire:key="action-buttons-{{ $lot->id }}">
                                    <button wire:click="edit({{ $lot->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $lot->id }})"
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
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                @if ($filterProductId || $showExpiredOnly || $showExpiringSoonOnly || $showAvailableOnly || $showActiveOnly)
                                    No se encontraron lotes con los filtros aplicados
                                @else
                                    No hay lotes registrados
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    @if ($editingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Lote</h3>

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
                        <!-- Initial Weight -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Peso Inicial (kg) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" step="0.001" wire:model="initial_weight"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('initial_weight')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Available Weight -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Peso Disponible (kg) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" step="0.001" wire:model="available_weight"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('available_weight')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Price per kg -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Precio por kg <span class="text-red-600">*</span>
                            </label>
                            <input type="number" step="0.01" wire:model="price_per_kg"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('price_per_kg')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Expires At -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento</label>
                            <input type="date" wire:model="expires_at"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('expires_at')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Active -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="active"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">Lote activo</span>
                        </label>
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

    <!-- Delete Confirmation Modal -->
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-6">
                    ¿Estás seguro de que deseas eliminar este lote? Esta acción no se puede deshacer.
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
