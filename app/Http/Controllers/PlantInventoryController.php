<?php

namespace App\Http\Controllers;

use App\Models\PlantSpecies;
use Inertia\Inertia;
use Inertia\Response;

class PlantInventoryController extends Controller
{
    public function index(): Response
    {
        $plants = PlantSpecies::with('inventory')
            ->orderBy('plant_name')
            ->paginate(15);

        return Inertia::render('plant-inventory/index', compact('plants'));
    }
}
