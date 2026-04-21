<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Order;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'orders' => Order::query()
                ->when($this->search, fn($q) => $q->where('reference', 'like', "%{$this->search}%")
                    ->orWhere('customer_email', 'like', "%{$this->search}%"))
                ->orderBy('created_at', 'desc')
                ->paginate(15),
        ];
    }
}; ?>

<div class="px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">Órdenes</h1>

    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por reference o email..."
        class="w-full mb-4 px-3 py-2 border rounded">

    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">Reference</th>
                    <th class="border p-2 text-left">Cliente</th>
                    <th class="border p-2 text-left">Total</th>
                    <th class="border p-2 text-left">Estado</th>
                    <th class="border p-2 text-left">Fecha</th>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border p-4 text-center text-gray-500">Sin órdenes</td>
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
                <button wire:click="previousPage" class="px-2 py-1 border rounded hover:bg-gray-100">←</button>
            @endif

            @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                @if ($page == $orders->currentPage())
                    <button class="px-2 py-1 bg-blue-500 text-white rounded">{{ $page }}</button>
                @else
                    <button wire:click="gotoPage({{ $page }})" class="px-2 py-1 border rounded">{{ $page }}</button>
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
