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

<div class="px-6 py-8">
    <!-- Header -->
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Órdenes</h1>
        <p class="text-sm text-gray-500 mt-0.5">E-commerce integrado con Wompi</p>
    </div>

    <!-- Stats -->
    <div class="mb-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="shrink-0 w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Aprobadas</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="shrink-0 w-10 h-10 bg-yellow-50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pendientes</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="shrink-0 w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Rechazadas</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['declined'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="mb-5 bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Referencia, email o nombre..."
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition-colors">
            </div>

            <select wire:model.live="filterStatus" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition-colors">
                <option value="">Todos los estados</option>
                <option value="PENDING">Pendiente</option>
                <option value="APPROVED">Aprobada</option>
                <option value="DECLINED">Rechazada</option>
                <option value="ERROR">Error</option>
                <option value="VOIDED">Anulada</option>
            </select>

            <select wire:model.live="filterMethod" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition-colors">
                <option value="">Todos los métodos</option>
                <option value="NEQUI">Nequi</option>
                <option value="CARD">Tarjeta</option>
                <option value="PSE">PSE</option>
            </select>

            <select wire:model.live="filterDate" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition-colors">
                <option value="">Todo el período</option>
                <option value="today">Hoy</option>
                <option value="week">Esta semana</option>
                <option value="month">Este mes</option>
            </select>
        </div>

        @if($search || $filterStatus || $filterMethod || $filterDate)
            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-400 font-medium">Activos:</span>
                @if($search)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 text-xs rounded-full border border-blue-100">
                        "{{ $search }}"
                        <button wire:click="$set('search', '')" class="hover:text-blue-900 leading-none ml-0.5">×</button>
                    </span>
                @endif
                @if($filterStatus)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 text-xs rounded-full border border-blue-100">
                        {{ $filterStatus }}
                        <button wire:click="$set('filterStatus', '')" class="hover:text-blue-900 leading-none ml-0.5">×</button>
                    </span>
                @endif
                @if($filterMethod)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 text-xs rounded-full border border-blue-100">
                        {{ $filterMethod }}
                        <button wire:click="$set('filterMethod', '')" class="hover:text-blue-900 leading-none ml-0.5">×</button>
                    </span>
                @endif
                @if($filterDate)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 text-xs rounded-full border border-blue-100">
                        {{ $filterDate }}
                        <button wire:click="$set('filterDate', '')" class="hover:text-blue-900 leading-none ml-0.5">×</button>
                    </span>
                @endif
                <button wire:click="clearFilters" class="ml-auto text-xs text-gray-400 hover:text-gray-700 underline transition-colors">
                    Limpiar todo
                </button>
            </div>
        @endif
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/60">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Referencia</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Método</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Fecha</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="font-mono text-sm font-semibold text-gray-800">{{ $order->reference }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="shrink-0 w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-xs font-bold text-blue-700">{{ strtoupper(substr($order->customer_name ?? 'U', 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 leading-tight">{{ $order->customer_name }}</p>
                                    <p class="text-xs text-gray-400 leading-tight">{{ $order->customer_email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($order->payment_method === 'NEQUI')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-purple-50 text-purple-700 text-xs font-medium rounded-md border border-purple-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    Nequi
                                </span>
                            @elseif($order->payment_method === 'CARD')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-md border border-blue-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    Tarjeta
                                </span>
                            @elseif($order->payment_method === 'PSE')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-teal-50 text-teal-700 text-xs font-medium rounded-md border border-teal-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                                    PSE
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-md">{{ $order->payment_method }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="text-sm font-semibold text-gray-900">${{ number_format($order->total_amount_cents / 100, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($order->status === 'APPROVED')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-green-50 text-green-700 text-xs font-medium rounded-full border border-green-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Aprobada
                                </span>
                            @elseif($order->status === 'PENDING')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-yellow-50 text-yellow-700 text-xs font-medium rounded-full border border-yellow-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span>
                                    Pendiente
                                </span>
                            @elseif($order->status === 'DECLINED')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-50 text-red-700 text-xs font-medium rounded-full border border-red-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Rechazada
                                </span>
                            @elseif($order->status === 'VOIDED')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-600 text-xs font-medium rounded-full border border-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Anulada
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-orange-50 text-orange-700 text-xs font-medium rounded-full border border-orange-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                    {{ $order->status }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="text-sm text-gray-700">{{ $order->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <a href="{{ route('orders.show', $order->reference) }}" wire:navigate
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors">
                                Ver
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                                <p class="text-gray-600 text-sm font-medium">Sin órdenes</p>
                                <p class="text-gray-400 text-xs">No hay órdenes que coincidan con los filtros aplicados</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Paginación -->
        @if($orders->hasPages())
            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                <span class="text-xs text-gray-500">
                    <span class="font-medium text-gray-700">{{ $orders->firstItem() ?? 0 }}</span>–<span class="font-medium text-gray-700">{{ $orders->lastItem() ?? 0 }}</span>
                    de <span class="font-medium text-gray-700">{{ $orders->total() }}</span>
                </span>
                <div class="flex items-center gap-1">
                    @if ($orders->onFirstPage())
                        <button disabled class="px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed">←</button>
                    @else
                        <button wire:click="previousPage" class="px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">←</button>
                    @endif

                    @foreach ($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                        @if ($page == $orders->currentPage())
                            <button class="px-2.5 py-1.5 text-xs bg-gray-900 text-white rounded-lg font-semibold">{{ $page }}</button>
                        @else
                            <button wire:click="gotoPage({{ $page }})" class="px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ $page }}</button>
                        @endif
                    @endforeach

                    @if ($orders->hasMorePages())
                        <button wire:click="nextPage" class="px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">→</button>
                    @else
                        <button disabled class="px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed">→</button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
