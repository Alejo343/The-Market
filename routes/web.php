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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Aquí irán tus rutas de productos, ventas, inventario...
    Route::resource('products', \App\Http\Controllers\Web\ProductController::class);
    Route::resource('sales', \App\Http\Controllers\Web\SaleController::class);
    Route::get('inventory', [\App\Http\Controllers\Web\InventoryController::class, 'index'])->name('inventory.index');
});
