<?php

use App\Http\Controllers\BuyerController;
use App\Http\Controllers\BuyerShopController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NurserySaleController;
use App\Http\Controllers\PlantInventoryController;
use App\Http\Controllers\PlantSpeciesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('welcome');
})->name('home');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::resource('buyers', BuyerController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        Route::resource('plant-species', PlantSpeciesController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['plant-species' => 'plant_species']);

        Route::resource('nursery-sales', NurserySaleController::class)
            ->only(['create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['nursery-sales' => 'nursery_sale']);
        Route::post('nursery-sales/{nursery_sale}/confirm', [NurserySaleController::class, 'confirm'])
            ->name('nursery-sales.confirm');
        Route::post('nursery-sales/{nursery_sale}/cancel', [NurserySaleController::class, 'cancel'])
            ->name('nursery-sales.cancel');
        Route::post('nursery-sales/{nursery_sale}/complete', [NurserySaleController::class, 'complete'])
            ->name('nursery-sales.complete');

        Route::resource('plant-inventory', PlantInventoryController::class)
            ->only(['index']);
    });

    Route::get('nursery-sales', [NurserySaleController::class, 'index'])
        ->name('nursery-sales.index');

    Route::middleware('role:buyer')->name('buyer.')->group(function () {
        Route::get('plants', [BuyerShopController::class, 'index'])->name('plants.index');
        Route::get('cart', [BuyerShopController::class, 'cart'])->name('cart.index');
        Route::post('cart/{plant}/items', [BuyerShopController::class, 'addToCart'])->name('cart.add');
        Route::patch('cart/{plant}/items', [BuyerShopController::class, 'updateCart'])->name('cart.update');
        Route::delete('cart/{plant}/items', [BuyerShopController::class, 'removeFromCart'])->name('cart.remove');
        Route::post('checkout', [BuyerShopController::class, 'checkout'])->name('checkout');
        Route::post('my-orders/{nursery_sale}/cancel', [NurserySaleController::class, 'cancelForBuyer'])
            ->name('orders.cancel');
        Route::delete('my-orders/{nursery_sale}', [NurserySaleController::class, 'destroyForBuyer'])
            ->name('orders.destroy');
    });

});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
