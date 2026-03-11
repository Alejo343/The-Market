<?php
use Livewire\Component;
use App\Services\SaleService;
use App\Services\UserService;
use App\Models\Sale;

new class extends Component {
    public string $search = '';
    public ?string $filterChannel = null;
    public ?int $filterUserId = null;
    public ?string $filterDate = null;
    public ?string $filterStartDate = null;
    public ?string $filterEndDate = null;
    public bool $showTodayOnly = false;

    public ?int $viewingId = null;
    public ?int $deletingId = null;

    public string $errorMessage = '';
    public string $successMessage = '';

    public function mount()
    {
        $this->resetMessages();
    }

    /**
     * Computed property para obtener las ventas filtradas
     */
    public function with(SaleService $saleService, UserService $userService): array
    {
        try {
            $sales = $saleService->list(channel: $this->filterChannel, userId: $this->filterUserId, date: $this->filterDate, startDate: $this->filterStartDate, endDate: $this->filterEndDate, today: $this->showTodayOnly, include: ['user', 'items']);

            return [
                'sales' => $sales,
                'users' => $userService->getAll(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error in with(): ' . $e->getMessage());
            $this->errorMessage = $this->translateError($e->getMessage());
            return [
                'sales' => collect(),
                'users' => collect(),
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

    public function updatedFilterChannel()
    {
        $this->resetMessages();
    }

    public function updatedFilterUserId()
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
        $this->search = '';
        $this->filterChannel = null;
        $this->filterUserId = null;
        $this->filterDate = null;
        $this->filterStartDate = null;
        $this->filterEndDate = null;
        $this->showTodayOnly = false;
        $this->resetMessages();
    }

    /**
     * Ver detalles de una venta
     */
    public function viewSale(int $id)
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
     * Elimina la venta
     */
    public function delete(SaleService $saleService)
    {
        $this->resetMessages();

        try {
            $sale = Sale::findOrFail($this->deletingId);
            $saleService->delete($sale);

            $this->successMessage = 'Venta eliminada exitosamente';
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
            'OPERATION_NOT_ALLOWED' => 'No se permite eliminar ventas. Contacte al administrador si necesita anular una venta.',
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
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Ventas</h1>
        <p class="text-gray-600">Historial y gestión de ventas</p>
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
            <!-- Channel Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Canal</label>
                <select wire:model.live="filterChannel"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los canales</option>
                    <option value="store">Mostrador</option>
                    <option value="online">En línea</option>
                </select>
            </div>

            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
                <select wire:model.live="filterUserId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los vendedores</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha específica</label>
                <input type="date" wire:model.live="filterDate"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Date Range -->
            <div class="md:col-span-2 lg:col-span-1">
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
                <span class="text-sm text-gray-700">Solo ventas de hoy</span>
            </label>

            <button wire:click="clearFilters"
                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                Limpiar filtros
            </button>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total de Ventas</div>
            <div class="text-2xl font-bold text-gray-900">{{ $sales->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Monto Total</div>
            <div class="text-2xl font-bold text-gray-900">${{ number_format($sales->sum('total'), 2) }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Promedio por Venta</div>
            <div class="text-2xl font-bold text-gray-900">
                ${{ $sales->count() > 0 ? number_format($sales->avg('total'), 2) : '0.00' }}
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vendedor
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Canal
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Items
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-gray-50 transition-colors" wire:key="sale-{{ $sale->id }}">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900">#{{ $sale->id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $sale->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $sale->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $sale->user->name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs rounded-full {{ $sale->channel === 'store' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $sale->channel === 'store' ? 'Mostrador' : 'En línea' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600">{{ $sale->items->count() }} items</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">${{ number_format($sale->total, 2) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    Imp: ${{ number_format($sale->tax_total, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2" wire:key="action-buttons-{{ $sale->id }}">
                                    <button wire:click="view({{ $sale->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                        title="Ver detalles">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $sale->id }})"
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
                                No se encontraron ventas con los filtros aplicados
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
            $viewingSale = $sales->firstWhere('id', $viewingId);
        @endphp
        @if ($viewingSale)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg p-6 max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Detalles de Venta #{{ $viewingSale->id }}
                        </h3>
                        <button wire:click="cancelView" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Sale Info -->
                        <div class="grid grid-cols-2 gap-4 pb-4 border-b">
                            <div>
                                <div class="text-sm text-gray-600">Fecha y Hora</div>
                                <div class="text-sm font-medium">{{ $viewingSale->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Vendedor</div>
                                <div class="text-sm font-medium">{{ $viewingSale->user->name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Canal</div>
                                <div class="text-sm font-medium">
                                    {{ $viewingSale->channel === 'store' ? 'Mostrador' : 'En línea' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Total Items</div>
                                <div class="text-sm font-medium">{{ $viewingSale->items->count() }}</div>
                            </div>
                        </div>

                        <!-- Items -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Items de la Venta</h4>
                            <div class="space-y-2">
                                @foreach ($viewingSale->items as $item)
                                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">{{ $item->item_type }}</div>
                                            <div class="text-xs text-gray-600">
                                                Cantidad:
                                                {{ $item->quantity }}{{ $item->item_type === 'App\\Models\\WeightLot' ? ' kg' : '' }}
                                                × ${{ number_format($item->unit_price, 2) }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium">
                                                ${{ number_format($item->subtotal, 2) }}</div>
                                            @if ($item->tax_amount > 0)
                                                <div class="text-xs text-gray-600">+ Imp:
                                                    ${{ number_format($item->tax_amount, 2) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="border-t pt-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">${{ number_format($viewingSale->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Impuestos:</span>
                                <span class="font-medium">${{ number_format($viewingSale->tax_total, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-2">
                                <span>Total:</span>
                                <span>${{ number_format($viewingSale->total, 2) }}</span>
                            </div>
                        </div>
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

    <!-- Delete Confirmation Modal -->
    @if ($deletingId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Eliminación</h3>
                <p class="text-gray-600 mb-6">
                    ¿Estás seguro de que deseas eliminar esta venta? Esta acción no se puede deshacer y revertirá el
                    inventario.
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
