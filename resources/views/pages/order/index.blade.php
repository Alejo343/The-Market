@php
$search = request('search', '');
$orders = \App\Models\Order::query()
    ->when($search, fn($q) => $q->where('reference', 'like', "%{$search}%")
        ->orWhere('customer_email', 'like', "%{$search}%"))
    ->orderBy('created_at', 'desc')
    ->paginate(15);
@endphp

<div class="px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Órdenes</h1>

    <form method="GET" class="mb-4">
        <input type="text" name="search" placeholder="Buscar por reference o email..."
            value="{{ $search }}" class="w-full px-3 py-2 border rounded">
    </form>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">Reference</th>
                    <th class="border p-2 text-left">Cliente</th>
                    <th class="border p-2 text-left">Total</th>
                    <th class="border p-2 text-left">Estado</th>
                    <th class="border p-2 text-left">Fecha</th>
                    <th class="border p-2 text-center">Items</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="border p-2 text-sm">{{ $order->reference }}</td>
                        <td class="border p-2 text-sm">{{ $order->customer_email }}</td>
                        <td class="border p-2 text-sm">${{ number_format($order->total_amount_cents / 100, 0) }}</td>
                        <td class="border p-2 text-sm">
                            <span class="px-2 py-1 rounded text-xs
                                @if($order->status === 'APPROVED') bg-green-100 text-green-800
                                @elseif($order->status === 'PENDING') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="border p-2 text-sm">{{ $order->created_at->format('d/m H:i') }}</td>
                        <td class="border p-2 text-center">
                            <button onclick="toggleModal('modal-{{ $order->id }}')" class="text-blue-600 hover:underline text-sm">Ver</button>
                        </td>
                    </tr>

                    <tr id="modal-{{ $order->id }}" style="display:none;">
                        <td colspan="6" class="border p-4 bg-gray-50">
                            <div class="space-y-2">
                                @forelse ($order->items_data as $item)
                                    <div class="text-sm border-l-4 border-blue-400 pl-3">
                                        <strong>{{ $item['name'] ?? 'Producto' }}</strong><br>
                                        Cantidad: {{ $item['quantity'] ?? 0 }} | Precio: ${{ number_format($item['price'] ?? 0, 0) }}
                                    </div>
                                @empty
                                    <p class="text-gray-500">Sin items</p>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="border p-4 text-center text-gray-500">Sin órdenes</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-between items-center text-sm">
        <span>{{ $orders->total() }} órdenes</span>
        <div class="flex gap-2">
            @if ($orders->onFirstPage())
                <button disabled class="px-2 py-1 border rounded text-gray-400">←</button>
            @else
                <a href="{{ $orders->previousPageUrl() }}" class="px-2 py-1 border rounded hover:bg-gray-100">←</a>
            @endif

            @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                @if ($page == $orders->currentPage())
                    <button class="px-2 py-1 bg-blue-500 text-white rounded">{{ $page }}</button>
                @else
                    <a href="{{ $url }}" class="px-2 py-1 border rounded hover:bg-gray-100">{{ $page }}</a>
                @endif
            @endforeach

            @if ($orders->hasMorePages())
                <a href="{{ $orders->nextPageUrl() }}" class="px-2 py-1 border rounded hover:bg-gray-100">→</a>
            @else
                <button disabled class="px-2 py-1 border rounded text-gray-400">→</button>
            @endif
        </div>
    </div>
</div>

<script>
function toggleModal(id) {
    const modal = document.getElementById(id);
    modal.style.display = modal.style.display === 'none' ? 'table-row' : 'none';
}
</script>
