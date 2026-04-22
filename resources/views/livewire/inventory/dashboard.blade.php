<?php

use Livewire\Volt\Component;
use App\Models\ProductVariant;
use App\Models\InventoryMovement;

new class extends Component {
    public string $stockFilter = 'low';

    public function with(): array
    {
        $lowStock = ProductVariant::with('product')
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('stock', '>', 0)
            ->orderBy('stock')
            ->limit(20)
            ->get();

        $outOfStock = ProductVariant::where('stock', 0)->count();
        $totalVariants = ProductVariant::count();
        $normalStock = $totalVariants - $outOfStock - $lowStock->count();

        $recentMovements = InventoryMovement::with('item')
            ->latest()
            ->limit(10)
            ->get();

        return [
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'normalStock' => $normalStock,
            'totalVariants' => $totalVariants,
            'recentMovements' => $recentMovements,
        ];
    }
}; ?>

<div class="px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard de Inventario</h1>
        <p class="text-gray-600">Monitoreo de stock y movimientos</p>
    </div>

    <!-- Stats -->
    <div class="mb-6 grid grid-cols-3 gap-4">
        <div class="bg-white rounded shadow p-4 border-l-4 border-green-500">
            <p class="text-gray-500 text-sm">Stock Normal</p>
            <p class="text-2xl font-bold text-green-600">{{ $normalStock }}</p>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-yellow-500">
            <p class="text-gray-500 text-sm">Stock Bajo</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $lowStock->count() }}</p>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-red-500">
            <p class="text-gray-500 text-sm">Agotados</p>
            <p class="text-2xl font-bold text-red-600">{{ $outOfStock }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <!-- Stock Bajo -->
        <div class="bg-white rounded shadow p-6">
            <h2 class="text-lg font-bold mb-4 border-b pb-3">Productos con Stock Bajo</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Producto</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Variante</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-600">Stock</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-600">Mín</th>
                            <th class="text-right py-2 px-2 font-medium text-gray-600">Falta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($lowStock as $variant)
                            <tr class="@if($variant->stock == 0) bg-red-50 @else bg-yellow-50 @endif hover:bg-gray-50">
                                <td class="py-2 px-2 font-medium">{{ $variant->product->name }}</td>
                                <td class="py-2 px-2">{{ $variant->presentation ?? '-' }}</td>
                                <td class="py-2 px-2 text-right">{{ $variant->stock }}</td>
                                <td class="py-2 px-2 text-right">{{ $variant->min_stock }}</td>
                                <td class="py-2 px-2 text-right font-semibold text-red-600">{{ $variant->min_stock - $variant->stock }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 px-2 text-center text-gray-500">Sin productos con stock bajo</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Últimos Movimientos -->
        <div class="bg-white rounded shadow p-6">
            <h2 class="text-lg font-bold mb-4 border-b pb-3">Últimos Movimientos</h2>
            <div class="space-y-3">
                @forelse ($recentMovements as $movement)
                    <div class="border-b pb-3 flex justify-between items-start">
                        <div>
                            <p class="font-medium">
                                @if ($movement->item_type === 'App\Models\ProductVariant')
                                    {{ $movement->item?->product?->name ?? 'Producto' }}
                                @elseif ($movement->item_type === 'App\Models\WeightLot')
                                    {{ $movement->item?->product?->name ?? 'Lote' }}
                                @else
                                    Movimiento
                                @endif
                            </p>
                            <p class="text-sm text-gray-500">
                                <span class="px-2 py-1 rounded text-xs
                                    @if($movement->type === 'in') bg-green-100 text-green-800
                                    @elseif($movement->type === 'out') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($movement->type) }}
                                </span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ $movement->quantity }}</p>
                            <p class="text-xs text-gray-500">{{ $movement->created_at->format('d/m H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">Sin movimientos recientes</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
