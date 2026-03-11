@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Ventas del día -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Ventas del Día</p>
                        <p class="text-3xl font-bold text-gray-800">${{ number_format($daily_sales, 2) }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Ventas del mes -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Ventas del Mes</p>
                        <p class="text-3xl font-bold text-gray-800">${{ number_format($monthly_sales, 2) }}</p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total productos -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Productos Activos</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $product_count }}</p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Gráfica de ventas -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Ventas Últimos 7 Días</h3>
                <!-- ✅ Usar clase de Tailwind -->
                <div class="h-72">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Productos con bajo stock -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Alertas de Stock Bajo</h3>
                <div class="space-y-3">
                    @forelse($low_stock_products as $variant)
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                            <div>
                                <p class="font-medium">{{ $variant->product->name }}</p>
                                <p class="text-sm text-gray-600">{{ $variant->presentation }}</p>
                            </div>
                            <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm">
                                Stock: {{ $variant->stock }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">No hay productos con stock bajo</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Ventas recientes -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-semibold">Ventas Recientes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendedor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Canal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($recent_sales as $sale)
                            <tr>
                                <td class="px-6 py-4 text-sm">#{{ $sale->id }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sale->user->name }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="px-2 py-1 text-xs rounded {{ $sale->channel == 'store' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $sale->channel == 'store' ? 'Mostrador' : 'En línea' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold">${{ number_format($sale->total, 2) }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('sales.show', $sale) }}"
                                        class="text-blue-600 hover:text-blue-800">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay ventas recientes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($sales_chart->pluck('date')),
                    datasets: [{
                        label: 'Ventas ($)',
                        data: @json($sales_chart->pluck('total')),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endpush
@endsection
