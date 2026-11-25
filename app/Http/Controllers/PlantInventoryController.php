<?php

namespace App\Http\Controllers;

use App\Services\PlantInventoryService;
use Inertia\Inertia;
use Inertia\Response;

class PlantInventoryController extends Controller
{
    public function index(PlantInventoryService $inventory): Response
    {
        return Inertia::render('plant-inventory/index', [
            'plants' => $inventory->listPlants(),
        ]);
    }
}
