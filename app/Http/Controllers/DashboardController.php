<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Ventas del día
        $dailySales = Sale::whereDate('created_at', today())->sum('total');

        // Ventas del mes
        $monthlySales = Sale::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        // Total de productos
        $totalProducts = Product::where('active', true)->count();

        // Productos con bajo stock
        $lowStockProducts = ProductVariant::where('stock', '<=', DB::raw('min_stock'))
            ->with('product')
            ->limit(5)
            ->get();

        // Ventas recientes
        $recentSales = Sale::with('user')
            ->latest()
            ->take(10)
            ->get();

        // Datos para gráfica de ventas (últimos 7 días)
        $salesChart = Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('dashboard.index', compact(
            'dailySales',
            'monthlySales',
            'totalProducts',
            'lowStockProducts',
            'recentSales',
            'salesChart'
        ));
    }
}
