<?php

namespace App\Services;

use App\Models\PlantSpecies;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlantInventoryService
{
    public function listPlants(): LengthAwarePaginator
    {
        return PlantSpecies::with('inventory')
            ->orderBy('plant_name')
            ->paginate(15);
    }
}
