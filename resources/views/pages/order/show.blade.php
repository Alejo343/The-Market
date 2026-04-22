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

<div class="px-4 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $order->reference }}</h1>
            <p class="text-gray-600 mt-1">Detalles de la orden de pago</p>
        </div>
        <span class="px-4 py-2 rounded text-lg font-semibold
            @if($order->status === 'APPROVED') bg-green-100 text-green-800
            @elseif($order->status === 'PENDING') bg-yellow-100 text-yellow-800
            @elseif($order->status === 'DECLINED') bg-red-100 text-red-800
            @else bg-orange-100 text-orange-800
            @endif">
            {{ $order->status }}
        </span>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <!-- Columna 1: Transacción -->
        <div class="col-span-2 space-y-6">
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-lg font-bold mb-4 border-b pb-3">Transacción</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ID Wompi:</span>
                        <span class="font-medium">{{ $order->transaction_id ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Método de Pago:</span>
                        <span class="font-medium">{{ $order->payment_method }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total:</span>
                        <span class="font-bold text-lg">${{ number_format($order->total_amount_cents / 100, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Creada:</span>
                        <span class="font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Actualizada:</span>
                        <span class="font-medium">{{ $order->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-lg font-bold mb-4 border-b pb-3">Items</h2>
                <div class="space-y-2">
                    @forelse ($order->items_data as $item)
                        <div class="flex justify-between border-b pb-2">
                            <div>
                                <p class="font-medium">{{ $item['name'] ?? 'Producto' }}</p>
                                <p class="text-sm text-gray-500">Cantidad: {{ $item['quantity'] ?? 0 }}</p>
                            </div>
                            <span class="font-semibold">${{ number_format($item['price'] ?? 0, 0) }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500">Sin items</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Columna 2: Cliente -->
        <div class="space-y-6">
            <div class="bg-white rounded shadow p-6">
                <h2 class="text-lg font-bold mb-4 border-b pb-3">Cliente</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-600 text-sm">Nombre</p>
                        <p class="font-medium">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Email</p>
                        <p class="font-medium">{{ $order->customer_email }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Teléfono</p>
                        <p class="font-medium">{{ $order->customer_phone }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Dirección</p>
                        <p class="font-medium">{{ $order->customer_address }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Ciudad</p>
                        <p class="font-medium">{{ $order->customer_city }}</p>
                    </div>
                </div>
            </div>

            @if ($order->notes)
                <div class="bg-white rounded shadow p-6">
                    <h2 class="text-lg font-bold mb-3 border-b pb-3">Notas</h2>
                    <p class="text-gray-700">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Volver -->
    <div class="mt-6">
        <a href="{{ route('orders.index') }}" wire:navigate class="inline-block px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            ← Volver a Órdenes
        </a>
    </div>
</div>
