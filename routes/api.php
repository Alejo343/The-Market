<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryMovementController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductMediaController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WeightLotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// RUTAS PÚBLICAS (sin autenticación)
// ============================================

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Categorías
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

//Productos
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

//Marca
Route::get('brands', [BrandController::class, 'index']);
Route::get('brands/{brand}', [BrandController::class, 'show']);

//impuestos
Route::get('taxes', [TaxController::class, 'index']);
Route::get('taxes/{tax}', [TaxController::class, 'show']);

//Variantes de productos
Route::get('product-variants', [ProductVariantController::class, 'index']);
Route::get('product-variants/{product_variant}', [ProductVariantController::class, 'show']);

//por peso
Route::get('weight-lots', [WeightLotController::class, 'index']);
Route::get('weight-lots/{weight_lot}', [WeightLotController::class, 'show']);




// ============================================
// RUTAS PROTEGIDAS (requieren autenticación)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // Usuarios
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/activate', [UserController::class, 'activate']);
    Route::post('users/{user}/deactivate', [UserController::class, 'deactivate']);


    // Usuario autenticado
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Categorías - Escritura
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Productos
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    //Marca
    Route::post('brands', [BrandController::class, 'store']);
    Route::put('brands/{brand}', [BrandController::class, 'update']);
    Route::delete('brands/{brand}', [BrandController::class, 'destroy']);

    //impuestos
    Route::post('taxes', [TaxController::class, 'store']);
    Route::put('taxes/{tax}', [TaxController::class, 'update']);
    Route::delete('taxes/{tax}', [TaxController::class, 'destroy']);

    // variantes de prodcutos
    Route::post('product-variants', [ProductVariantController::class, 'store']);
    Route::put('product-variants/{product_variant}', [ProductVariantController::class, 'update']);
    Route::delete('product-variants/{product_variant}', [ProductVariantController::class, 'destroy']);

    //Peso
    Route::post('weight-lots', [WeightLotController::class, 'store']);
    Route::put('weight-lots/{weight_lot}', [WeightLotController::class, 'update']);
    Route::delete('weight-lots/{weight_lot}', [WeightLotController::class, 'destroy']);

    //ventas
    Route::get('sales', [SaleController::class, 'index']);
    Route::post('sales', [SaleController::class, 'store']);
    Route::get('sales/{sale}', [SaleController::class, 'show']);

    //movimeintos de inventario
    Route::get('inventory-movements', [InventoryMovementController::class, 'index']);
    Route::post('inventory-movements', [InventoryMovementController::class, 'store']);
    Route::get('inventory-movements/{inventory_movement}', [InventoryMovementController::class, 'show']);


    Route::get('media', [MediaController::class, 'index']);
    Route::post('media', [MediaController::class, 'store']);
    Route::get('media/{media}', [MediaController::class, 'show']);
    Route::put('media/{media}', [MediaController::class, 'update']);
    Route::delete('media/{media}', [MediaController::class, 'destroy']);

    // Listar imágenes de un producto
    Route::get('products/{product}/media', [ProductMediaController::class, 'index']);

    // Subir una imagen
    Route::post('products/{product}/media', [ProductMediaController::class, 'store']);

    // Subir múltiples imágenes
    Route::post('products/{product}/media/multiple', [ProductMediaController::class, 'storeMultiple']);

    // Establecer imagen principal
    Route::post('products/{product}/media/{media}/set-primary', [ProductMediaController::class, 'setPrimary']);

    // Reordenar imágenes
    Route::post('products/{product}/media/reorder', [ProductMediaController::class, 'reorder']);

    // Eliminar una imagen específica
    Route::delete('products/{product}/media/{media}', [ProductMediaController::class, 'destroy']);

    // Eliminar todas las imágenes
    Route::delete('products/{product}/media', [ProductMediaController::class, 'destroyAll']);
});
