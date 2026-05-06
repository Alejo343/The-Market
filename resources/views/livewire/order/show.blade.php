<?php

use Livewire\Volt\Component;
use App\Models\Order;

new class extends Component {
    public string $reference = '';

    public function mount()
    {
        $this->reference = request()->route('reference');
    }

    public function with(): array
    {
        $order = Order::where('reference', $this->reference)->firstOrFail();

        return ['order' => $order];
    }
}; ?>

<div class="px-6 py-8 max-w-5xl">
    <!-- Back -->
    <div class="mb-6">
        <a href="{{ route('orders.index') }}" wire:navigate
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a Órdenes
        </a>
    </div>

    <!-- Header card -->
    <div class="mb-6 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-widest mb-1">Referencia</p>
                <h1 class="text-2xl font-bold font-mono text-gray-900 tracking-tight">{{ $order->reference }}</h1>
                <p class="text-sm text-gray-400 mt-1">{{ $order->created_at->format('d/m/Y \a \l\a\s H:i') }}</p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                @if($order->payment_method === 'NEQUI')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 text-purple-700 text-sm font-medium rounded-lg border border-purple-100">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        Nequi
                    </span>
                @elseif($order->payment_method === 'CARD')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg border border-blue-100">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Tarjeta
                    </span>
                @elseif($order->payment_method === 'PSE')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-50 text-teal-700 text-sm font-medium rounded-lg border border-teal-100">
                        <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                        PSE
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg">
                        {{ $order->payment_method }}
                    </span>
                @endif

                @if($order->status === 'APPROVED')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 text-sm font-semibold rounded-lg border border-green-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Aprobada
                    </span>
                @elseif($order->status === 'PENDING')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 text-yellow-700 text-sm font-semibold rounded-lg border border-yellow-200">
                        <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>
                        Pendiente
                    </span>
                @elseif($order->status === 'DECLINED')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-700 text-sm font-semibold rounded-lg border border-red-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Rechazada
                    </span>
                @elseif($order->status === 'VOIDED')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 text-gray-600 text-sm font-semibold rounded-lg border border-gray-200">
                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                        Anulada
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 text-orange-700 text-sm font-semibold rounded-lg border border-orange-200">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        {{ $order->status }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Columna principal: Transacción + Productos -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Transacción -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Transacción</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-x-8 gap-y-5">
                        <div>
                            <dt class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">ID Wompi</dt>
                            <dd class="text-sm font-mono font-medium text-gray-800 break-all">{{ $order->transaction_id ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Total</dt>
                            <dd class="text-2xl font-bold text-gray-900">${{ number_format($order->total_amount_cents / 100, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Creada</dt>
                            <dd class="text-sm text-gray-700">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Actualizada</dt>
                            <dd class="text-sm text-gray-700">{{ $order->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Productos -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Productos</h2>
                </div>

                @forelse ($order->items_data as $item)
                    <div class="flex items-center justify-between px-6 py-3.5 border-b border-gray-50 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center">
                                <span class="text-xs font-bold text-gray-500">{{ $item['quantity'] ?? 1 }}x</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $item['name'] ?? 'Producto' }}</p>
                                <p class="text-xs text-gray-400">Cantidad: {{ $item['quantity'] ?? 0 }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-gray-800">${{ number_format($item['price'] ?? 0, 0, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-gray-400 text-sm">Sin productos</p>
                    </div>
                @endforelse

                <div class="flex justify-between items-center px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <span class="text-sm font-semibold text-gray-600">Total de la orden</span>
                    <span class="text-lg font-bold text-gray-900">${{ number_format($order->total_amount_cents / 100, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Columna lateral: Cliente + Notas -->
        <div class="space-y-5">
            <!-- Cliente -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Cliente</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-50">
                        <div class="shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-sm font-bold text-blue-700">{{ strtoupper(substr($order->customer_name ?? 'U', 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $order->customer_name }}</p>
                            <p class="text-xs text-gray-500 break-all">{{ $order->customer_email }}</p>
                        </div>
                    </div>

                    <div class="space-y-3.5">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Teléfono</p>
                                <p class="text-sm text-gray-800">{{ $order->customer_phone ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Dirección</p>
                                <p class="text-sm text-gray-800">{{ $order->customer_address ?? '—' }}</p>
                                @if($order->customer_city)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $order->customer_city }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($order->notes)
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-900">Notas</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-700 leading-relaxed">{{ $order->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
