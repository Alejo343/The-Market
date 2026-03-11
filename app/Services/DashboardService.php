<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Obtiene ventas del día
     */
    public function getDailySales(): float
    {
        return Sale::whereDate('created_at', today())->sum('total');
    }

    /**
     * Obtiene ventas del mes
     */
    public function getMonthlySales(): float
    {
        return Sale::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
    }

    /**
     * Cuenta productos activos
     */
    public function getProductCount(): int
    {
        return Product::where('active', true)->count();
    }

    /**
     * Obtiene productos con bajo stock
     */
    public function getLowStockProducts(int $limit = 5): Collection
    {
        return ProductVariant::where('stock', '<=', DB::raw('min_stock'))
            ->with('product')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene ventas recientes
     */
    public function getRecentSales(int $limit = 10): Collection
    {
        return Sale::with('user')
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Obtiene datos para gráfica de ventas (últimos N días)
     */
    public function getSalesChartData(int $days = 7): Collection
    {
        return Sale::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Obtiene resumen completo del dashboard
     */
    public function getSummary(): array
    {
        return [
            'daily_sales' => $this->getDailySales(),
            'monthly_sales' => $this->getMonthlySales(),
            'product_count' => $this->getProductCount(),
            'low_stock_products' => $this->getLowStockProducts(),
            'recent_sales' => $this->getRecentSales(),
            'sales_chart' => $this->getSalesChartData(),
        ];
    }
}
