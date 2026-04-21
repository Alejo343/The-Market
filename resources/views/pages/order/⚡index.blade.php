<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Order;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterMethod = '';
    public string $filterDate = '';
    public int $perPage = 20;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterMethod()
    {
        $this->resetPage();
    }

    public function updatedFilterDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterMethod = '';
        $this->filterDate = '';
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Order::query()
            ->when($this->search, function ($q) {
                return $q->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('customer_email', 'like', "%{$this->search}%")
                    ->orWhere('customer_name', 'like', "%{$this->search}%");
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterMethod, fn($q) => $q->where('payment_method', $this->filterMethod))
            ->when($this->filterDate === 'today', fn($q) => $q->whereDate('created_at', today()))
            ->when($this->filterDate === 'week', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()]))
            ->when($this->filterDate === 'month', fn($q) => $q->whereMonth('created_at', now()->month))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return [
            'orders' => $query,
            'stats' => [
                'total' => Order::count(),
                'approved' => Order::where('status', 'APPROVED')->count(),
                'pending' => Order::where('status', 'PENDING')->count(),
                'declined' => Order::where('status', 'DECLINED')->count(),
            ],
        ];
    }
}; ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Órdenes de Pago</h1>
        <p class="text-gray-600">Gestiona todas las órdenes de e-commerce integradas con Wompi</p>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-gray-500 text-sm">Total Órdenes</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-gray-500 text-sm">Aprobadas</p>
            <p class="text-3xl font-bold text-green-600">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-gray-500 text-sm">Pendientes</p>
            <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-gray-500 text-sm">Rechazadas</p>
            <p class="text-3xl font-bold text-red-600">{{ $stats['declined'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Reference, email o nombre..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select wire:model.live="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="PENDING">Pendiente</option>
                    <option value="APPROVED">Aprobada</option>
                    <option value="DECLINED">Rechazada</option>
                    <option value="ERROR">Error</option>
                    <option value="VOIDED">Anulada</option>
                </select>
            </div>

            <!-- Method Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                <select wire:model.live="filterMethod" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="NEQUI">Nequi</option>
                    <option value="CARD">Tarjeta</option>
                    <option value="PSE">PSE</option>
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                <select wire:model.live="filterDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="today">Hoy</option>
                    <option value="week">Esta Semana</option>
                    <option value="month">Este Mes</option>
                </select>
            </div>
        </div>

        <!-- Clear Filters & Per Page -->
        <div class="mt-4 flex justify-between items-center">
            <button wire:click="clearFilters" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                Limpiar Filtros
            </button>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Por página:</label>
                <select wire:model.live="perPage" class="px-3 py-1 border border-gray-300 rounded-lg text-sm">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $order->reference }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div>{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-400">{{ $order->customer_email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $order->payment_method }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            ${{ number_format($order->total_amount_cents / 100, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($order->status === 'APPROVED')
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Aprobada</span>
                            @elseif ($order->status === 'PENDING')
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                            @elseif ($order->status === 'DECLINED')
                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Rechazada</span>
                            @elseif ($order->status === 'VOIDED')
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Anulada</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">{{ $order->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('orders.show', $order->reference) }}" wire:navigate
                                class="text-blue-600 hover:text-blue-800 font-medium">
                                Ver
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No hay órdenes que coincidan con los filtros
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="bg-white px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Mostrando {{ $orders->firstItem() ?? 0 }} a {{ $orders->lastItem() ?? 0 }} de {{ $orders->total() }} órdenes
            </div>
            <div class="flex gap-2">
                @if ($orders->onFirstPage())
                    <button disabled class="px-3 py-1 text-sm border border-gray-300 text-gray-400 rounded-lg cursor-not-allowed">← Anterior</button>
                @else
                    <button wire:click="previousPage" class="px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">← Anterior</button>
                @endif

                @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                    @if ($page == $orders->currentPage())
                        <button class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg">{{ $page }}</button>
                    @else
                        <button wire:click="gotoPage({{ $page }})" class="px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">{{ $page }}</button>
                    @endif
                @endforeach

                @if ($orders->hasMorePages())
                    <button wire:click="nextPage" class="px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Siguiente →</button>
                @else
                    <button disabled class="px-3 py-1 text-sm border border-gray-300 text-gray-400 rounded-lg cursor-not-allowed">Siguiente →</button>
                @endif
            </div>
        </div>
    </div>
</div>
