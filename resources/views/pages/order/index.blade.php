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
            ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")
                ->orWhere('customer_email', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterMethod, fn($q) => $q->where('payment_method', $this->filterMethod))
            ->when($this->filterDate === 'today', fn($q) => $q->whereDate('created_at', today()))
            ->when($this->filterDate === 'week', fn($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()]))
            ->when($this->filterDate === 'month', fn($q) => $q->whereMonth('created_at', now()->month))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

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

<div class="px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Órdenes</h1>
        <p class="text-gray-600">Gestiona las órdenes de e-commerce integradas con Wompi</p>
    </div>

    <!-- Stats -->
    <div class="mb-6 grid grid-cols-4 gap-4">
        <div class="bg-white rounded shadow p-4">
            <p class="text-gray-500 text-sm">Total</p>
            <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-green-500">
            <p class="text-gray-500 text-sm">Aprobadas</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-yellow-500">
            <p class="text-gray-500 text-sm">Pendientes</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-red-500">
            <p class="text-gray-500 text-sm">Rechazadas</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['declined'] }}</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="mb-6 bg-white rounded shadow p-4">
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium mb-1">Buscar</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Reference, email o nombre..."
                    class="w-full px-3 py-2 border rounded">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Estado</label>
                <select wire:model.live="filterStatus" class="w-full px-3 py-2 border rounded">
                    <option value="">Todos</option>
                    <option value="PENDING">Pendiente</option>
                    <option value="APPROVED">Aprobada</option>
                    <option value="DECLINED">Rechazada</option>
                    <option value="ERROR">Error</option>
                    <option value="VOIDED">Anulada</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Método</label>
                <select wire:model.live="filterMethod" class="w-full px-3 py-2 border rounded">
                    <option value="">Todos</option>
                    <option value="NEQUI">Nequi</option>
                    <option value="CARD">Tarjeta</option>
                    <option value="PSE">PSE</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Período</label>
                <select wire:model.live="filterDate" class="w-full px-3 py-2 border rounded">
                    <option value="">Todos</option>
                    <option value="today">Hoy</option>
                    <option value="week">Esta Semana</option>
                    <option value="month">Este Mes</option>
                </select>
            </div>
        </div>
        <button wire:click="clearFilters" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Limpiar Filtros
        </button>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600">Reference</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600">Método</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600">Total</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-600">Fecha</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">{{ $order->reference }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div>{{ $order->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $order->customer_email }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $order->payment_method }}</td>
                        <td class="px-4 py-3 text-sm font-semibold">${{ number_format($order->total_amount_cents / 100, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded text-xs
                                @if($order->status === 'APPROVED') bg-green-100 text-green-800
                                @elseif($order->status === 'PENDING') bg-yellow-100 text-yellow-800
                                @elseif($order->status === 'DECLINED') bg-red-100 text-red-800
                                @else bg-orange-100 text-orange-800
                                @endif">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('orders.show', $order->reference) }}" wire:navigate
                                class="text-blue-600 hover:text-blue-800 font-medium text-sm">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Sin órdenes</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="px-4 py-3 border-t flex items-center justify-between text-sm">
            <span class="text-gray-600">{{ $orders->firstItem() ?? 0 }} a {{ $orders->lastItem() ?? 0 }} de {{ $orders->total() }}</span>
            <div class="flex gap-1">
                @if ($orders->onFirstPage())
                    <button disabled class="px-2 py-1 border rounded text-gray-400">←</button>
                @else
                    <button wire:click="previousPage" class="px-2 py-1 border rounded hover:bg-gray-100">←</button>
                @endif

                @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                    @if ($page == $orders->currentPage())
                        <button class="px-2 py-1 bg-blue-500 text-white rounded">{{ $page }}</button>
                    @else
                        <button wire:click="gotoPage({{ $page }})" class="px-2 py-1 border rounded hover:bg-gray-100">{{ $page }}</button>
                    @endif
                @endforeach

                @if ($orders->hasMorePages())
                    <button wire:click="nextPage" class="px-2 py-1 border rounded hover:bg-gray-100">→</button>
                @else
                    <button disabled class="px-2 py-1 border rounded text-gray-400">→</button>
                @endif
            </div>
        </div>
    </div>
</div>
