<?php

use App\Http\Controllers\BuyerController;
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

    Route::get('/dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('plant-species', PlantSpeciesController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['plant-species' => 'plant_species']);

    Route::resource('nursery-sales', NurserySaleController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['nursery-sales' => 'nursery_sale']);
    Route::post('nursery-sales/{nursery_sale}/confirm', [NurserySaleController::class, 'confirm'])
        ->name('nursery-sales.confirm');

    Route::resource('plant-inventory', PlantInventoryController::class)
        ->only(['index']);

    Route::resource('buyers', BuyerController::class)
        ->only(['index']);
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
