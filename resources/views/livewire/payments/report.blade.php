<?php

use Livewire\Volt\Component;
use App\Models\Order;

new class extends Component {
    public string $period = '30d';

    public function updated($property)
    {
        if ($property === 'period') {
            $this->dispatch('periodChanged');
        }
    }

    public function with(): array
    {
        $from = match($this->period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            'year' => now()->startOfYear(),
        };

        $approvedOrders = Order::where('status', 'APPROVED')
            ->where('created_at', '>=', $from);

        $totalRevenue = $approvedOrders->sum('total_amount_cents');
        $totalOrders = Order::where('created_at', '>=', $from)->count();
        $approvedCount = $approvedOrders->count();
        $conversionRate = $totalOrders > 0 ? round(($approvedCount / $totalOrders) * 100, 1) : 0;
        $avgOrder = $approvedCount > 0 ? round($totalRevenue / $approvedCount) : 0;

        $byMethod = Order::where('created_at', '>=', $from)
            ->where('status', 'APPROVED')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total_amount_cents) as total')
            ->groupBy('payment_method')
            ->get();

        $daily = Order::where('created_at', '>=', $from)
            ->where('status', 'APPROVED')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount_cents) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($d) => [
                'date' => $d->date,
                'total' => $d->total,
                'count' => $d->count,
            ]);

        return [
            'kpis' => [
                'revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'conversion' => $conversionRate,
                'avgOrder' => $avgOrder,
                'approved' => $approvedCount,
            ],
            'byMethod' => $byMethod,
            'daily' => $daily,
        ];
    }
}; ?>

<div class="px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Reporte de Pagos</h1>
        <p class="text-gray-600">Análisis de transacciones y ingresos</p>
    </div>

    <!-- Selector de período -->
    <div class="mb-6 flex gap-2">
        @foreach (['7d' => 'Últimos 7 días', '30d' => 'Últimos 30 días', '90d' => 'Últimos 90 días', 'year' => 'Este año'] as $val => $label)
            <button wire:click="$set('period', '{{ $val }}')"
                class="px-4 py-2 rounded
                    @if($period === $val) bg-blue-600 text-white @else bg-gray-200 text-gray-700 hover:bg-gray-300 @endif">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- KPIs -->
    <div class="mb-6 grid grid-cols-5 gap-4">
        <div class="bg-white rounded shadow p-4">
            <p class="text-gray-600 text-sm mb-1">Ingresos Totales</p>
            <p class="text-2xl font-bold">${{ number_format($kpis['revenue'] / 100, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <p class="text-gray-600 text-sm mb-1">Órdenes</p>
            <p class="text-2xl font-bold">{{ $kpis['orders'] }}</p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <p class="text-gray-600 text-sm mb-1">Tasa Conversión</p>
            <p class="text-2xl font-bold">{{ $kpis['conversion'] }}%</p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <p class="text-gray-600 text-sm mb-1">Ticket Promedio</p>
            <p class="text-2xl font-bold">${{ number_format($kpis['avgOrder'] / 100, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded shadow p-4 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm mb-1">Aprobadas</p>
            <p class="text-2xl font-bold text-green-600">{{ $kpis['approved'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <!-- Método de Pago -->
        <div class="bg-white rounded shadow p-6">
            <h2 class="text-lg font-bold mb-4 border-b pb-3">Por Método de Pago</h2>
            <div class="space-y-3">
                @php
                    $totalByMethod = $byMethod->sum('total');
                @endphp
                @forelse ($byMethod as $method)
                    @php
                        $percentage = $totalByMethod > 0 ? round(($method->total / $totalByMethod) * 100, 1) : 0;
                    @endphp
                    <div class="flex justify-between items-center border-b pb-2">
                        <div>
                            <p class="font-medium">{{ $method->payment_method }}</p>
                            <p class="text-sm text-gray-500">{{ $method->count }} órdenes</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">${{ number_format($method->total / 100, 0) }}</p>
                            <p class="text-xs text-gray-500">{{ $percentage }}%</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">Sin datos</p>
                @endforelse
            </div>
        </div>

        <!-- Ingresos Diarios -->
        <div class="bg-white rounded shadow p-6">
            <h2 class="text-lg font-bold mb-4 border-b pb-3">Ingresos Diarios</h2>
            <div class="space-y-2">
                @forelse ($daily->reverse() as $day)
                    <div class="flex justify-between items-center border-b pb-2">
                        <div>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</p>
                            <p class="text-sm text-gray-500">{{ $day['count'] }} órdenes</p>
                        </div>
                        <p class="font-semibold">${{ number_format($day['total'] / 100, 0) }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">Sin datos</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
