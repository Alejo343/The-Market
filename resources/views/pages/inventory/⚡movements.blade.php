<?php
use Livewire\Component;
use App\Services\InventoryMovementService;
use App\Services\UserService;
use App\Models\InventoryMovement;

new class extends Component {
    public ?string $filterType = null;
    public ?int $filterUserId = null;
    public ?string $filterItemType = null;
    public ?string $filterDate = null;
    public ?string $filterStartDate = null;
    public ?string $filterEndDate = null;
    public bool $showTodayOnly = false;

    public ?int $viewingId = null;

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener los movimientos filtrados
     */
    public function with(InventoryMovementService $movementService, UserService $userService): array
    {
        try {
            $movements = $movementService->list(type: $this->filterType, userId: $this->filterUserId, itemType: $this->filterItemType, date: $this->filterDate, startDate: $this->filterStartDate, endDate: $this->filterEndDate, today: $this->showTodayOnly, include: []);

            return [
                'movements' => $movements,
                'users' => $userService->getAll(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = 'Error al cargar movimientos';
            return [
                'movements' => collect(),
                'users' => collect(),
            ];
        }
    }

    /**
     * Actualiza los filtros
     */
    public function updatedFilterType()
    {
        $this->resetMessages();
    }

    public function updatedFilterUserId()
    {
        $this->resetMessages();
    }

    public function updatedFilterItemType()
    {
        $this->resetMessages();
    }

    public function updatedFilterDate()
    {
        $this->resetMessages();
    }

    public function updatedFilterStartDate()
    {
        $this->resetMessages();
    }

    public function updatedFilterEndDate()
    {
        $this->resetMessages();
    }

    public function updatedShowTodayOnly()
    {
        $this->resetMessages();
    }

    /**
     * Limpia todos los filtros
     */
    public function clearFilters()
    {
        $this->filterType = null;
        $this->filterUserId = null;
        $this->filterItemType = null;
        $this->filterDate = null;
        $this->filterStartDate = null;
        $this->filterEndDate = null;
        $this->showTodayOnly = false;
        $this->resetMessages();
    }

    /**
     * Ver detalles de un movimiento
     */
    public function viewMovement(int $id)
    {
        $this->viewingId = $id;
    }

    /**
     * Cancela la vista de detalles
     */
    public function cancelView()
    {
        $this->viewingId = null;
    }

    /**
     * Resetea los mensajes de error y éxito
     */
    private function resetMessages()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Movimientos de Inventario</h1>
        <p class="text-gray-600">Historial de entradas, salidas y ajustes de inventario</p>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Movimiento</label>
                <select wire:model.live="filterType"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los tipos</option>
                    <option value="in">Entrada</option>
                    <option value="out">Salida</option>
                    <option value="adjustment">Ajuste</option>
                </select>
            </div>

            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                <select wire:model.live="filterUserId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los usuarios</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Item Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Producto</label>
                <select wire:model.live="filterItemType"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="variant">Variantes (Unidad)</option>
                    <option value="weightlot">Lotes (Peso)</option>
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha específica</label>
                <input type="date" wire:model.live="filterDate"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Date Range -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">O rango de fechas</label>
                <div class="flex gap-2">
                    <input type="date" wire:model.live="filterStartDate" placeholder="Desde"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <input type="date" wire:model.live="filterEndDate" placeholder="Hasta"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="showTodayOnly"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm text-gray-700">Solo movimientos de hoy</span>
            </label>

            <button wire:click="clearFilters"
                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                Limpiar filtros
            </button>
        </div>
    </div>

    <!-- Actions -->
    <div class="mb-6 flex justify-end">
        <a href="/inventory-movements/create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Movimiento
        </a>
    </div>

    <!-- Movements Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Producto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cantidad
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usuario
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($movements as $movement)
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="movement-{{ $movement->id }}">
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $movement->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $movement->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $typeColors = [
                                        'in' => 'bg-green-100 text-green-800',
                                        'out' => 'bg-red-100 text-red-800',
                                        'adjustment' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                    $typeNames = [
                                        'in' => 'Entrada',
                                        'out' => 'Salida',
                                        'adjustment' => 'Ajuste',
                                    ];
                                @endphp
                                <span
                                    class="px-2 py-1 text-xs rounded-full {{ $typeColors[$movement->type] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $typeNames[$movement->type] ?? $movement->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    {{ $movement->item?->product?->name ?? 'Producto eliminado' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    @if ($movement->item_type === 'App\Models\ProductVariant')
                                        Variante: {{ $movement->item?->presentation ?? '-' }}
                                    @else
                                        Lote de peso
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900">
                                    {{ number_format($movement->quantity, $movement->item_type === 'App\Models\WeightLot' ? 3 : 0) }}
                                    {{ $movement->item_type === 'App\Models\WeightLot' ? ' kg' : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $movement->user->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="viewMovement({{ $movement->id }})"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                    title="Ver detalles">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                No se encontraron movimientos con los filtros aplicados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Details Modal -->
    @if ($viewingId)
        @php
            $viewingMovement = $movements->firstWhere('id', $viewingId);
        @endphp
        @if ($viewingMovement)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Detalles del Movimiento
                            #{{ $viewingMovement->id }}</h3>
                        <button wire:click="cancelView" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 pb-4 border-b">
                            <div>
                                <div class="text-sm text-gray-600">Fecha y Hora</div>
                                <div class="text-sm font-medium">
                                    {{ $viewingMovement->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Usuario</div>
                                <div class="text-sm font-medium">{{ $viewingMovement->user->name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Tipo de Movimiento</div>
                                <div class="text-sm font-medium">
                                    @php
                                        $typeNames = ['in' => 'Entrada', 'out' => 'Salida', 'adjustment' => 'Ajuste'];
                                    @endphp
                                    {{ $typeNames[$viewingMovement->type] ?? $viewingMovement->type }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Cantidad</div>
                                <div class="text-sm font-medium">
                                    {{ number_format($viewingMovement->quantity, $viewingMovement->item_type === 'App\Models\WeightLot' ? 3 : 0) }}
                                    {{ $viewingMovement->item_type === 'App\Models\WeightLot' ? ' kg' : ' unidades' }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="text-sm text-gray-600 mb-1">Producto</div>
                            <div class="text-sm font-medium">
                                {{ $viewingMovement->item?->product?->name ?? 'Producto eliminado' }}</div>
                            @if ($viewingMovement->item_type === 'App\Models\ProductVariant')
                                <div class="text-xs text-gray-500">Presentación:
                                    {{ $viewingMovement->item?->presentation ?? '-' }}</div>
                            @else
                                <div class="text-xs text-gray-500">Lote de peso</div>
                            @endif
                        </div>

                        @if ($viewingMovement->note)
                            <div>
                                <div class="text-sm text-gray-600 mb-1">Nota</div>
                                <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded">{{ $viewingMovement->note }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end mt-6 pt-4 border-t">
                        <button wire:click="cancelView"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
