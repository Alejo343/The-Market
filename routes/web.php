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

    Route::view('/variants', 'variants.index')->name('variants.index');

    Route::livewire('/product-variants', 'pages::product-variant.index');
    Route::livewire('/product-variants/create', 'pages::product-variant.create');

    Route::livewire('/weight-lots', 'pages::weight-lot.index');
    Route::livewire('/weight-lots/create', 'pages::weight-lot.create');

    Route::view('/inventory/movements', 'inventory.movements')->name('inventory.movements');
    Route::view('/inventory/alerts', 'inventory.alerts')->name('inventory.alerts');

    // Catálogo
    Route::livewire('/categories', 'pages::category.index');
    Route::livewire('/categories/create', 'pages::category.create');
    Route::livewire('/brands', 'pages::brand.index');
    Route::livewire('/brands/create', 'pages::brand.create');
    Route::view('/taxes', 'taxes.index')->name('taxes.index');

    // Reportes
    Route::livewire('/reports/sales', 'pages::sale.index');

    Route::view('/reports/top-products', 'reports.top-products')->name('reports.top-products');
    Route::view('/reports/inventory', 'reports.inventory')->name('reports.inventory');
    Route::view('/reports/price-history', 'reports.price-history')->name('reports.price-history');

    // Configuración
    Route::view('/users', 'users.index')->name('users.index');
    Route::view('/profile', 'profile.edit')->name('profile.edit');
});
