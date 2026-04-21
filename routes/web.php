<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Redirigir raíz al login
Route::get('/', function () {
    return redirect()->route('login');
});


// Rutas de autenticación (guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas protegidas (autenticadas)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Ventas
    Route::view('/sales', 'sales.index')->name('sales.index');
    Route::view('/sales/create', 'sales.create')->name('sales.create');
    Route::view('/sales/{id}', 'sales.show')->name('sales.show');

    // Inventario
    Route::livewire('/products', 'pages::product.index');
    Route::livewire('/products/create', 'pages::product.create');

    Route::livewire('/product-variants', 'pages::product-variant.index');
    Route::livewire('/product-variants/create', 'pages::product-variant.create')->name('product-variants.create');
    Route::livewire('/weight-lots', 'pages::weight-lot.index');
    Route::livewire('/weight-lots/create', 'pages::weight-lot.create');
    Route::livewire('/inventory/movements', 'pages::inventory.movements');
    Route::livewire('/regions', 'pages::region.index')->name('regions.index');
    Route::livewire('/regions/create', 'pages::region.create')->name('regions.create');


    Route::view('/inventory/alerts', 'inventory.alerts')->name('inventory.alerts');

    // Catálogo
    Route::livewire('/categories', 'pages::category.index');
    Route::livewire('/categories/create', 'pages::category.create');
    Route::livewire('/brands', 'pages::brand.index');
    Route::livewire('/brands/create', 'pages::brand.create');
    Route::livewire('/taxes', 'pages::tax.index');
    Route::livewire('/taxes/create', 'pages::tax.create');

    // Reportes
    Route::livewire('/reports/sales', 'pages::sale.index');

    // Route::view('/reports/top-products', 'reports.top-products')->name('reports.top-products');
    // Route::view('/reports/inventory', 'reports.inventory')->name('reports.inventory');
    // Route::view('/reports/price-history', 'reports.price-history')->name('reports.price-history');

    // Pedidos (E-commerce / Wompi)
    Route::livewire('/orders', 'pages::order.index')->name('orders.index');
    Route::livewire('/orders/{reference}', 'pages::order.show')->name('orders.show');
    Route::livewire('/payments/report', 'pages::payments.report')->name('payments.report');
    Route::livewire('/inventory/dashboard', 'pages::inventory.dashboard')->name('inventory.dashboard');

    // Configuración
    // Route::view('/users', 'users.index')->name('users.index');
    // Route::view('/profile', 'profile.edit')->name('profile.edit');
});
