<?php

namespace App\Services;

use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use Illuminate\Support\Facades\DB;

class PlantSpeciesService
{
    public function createPlantSpecies(array $data): PlantSpecies
    {
        $quantityAvailable = $data['quantity_available'] ?? 0;
        unset($data['quantity_available']);

        return DB::transaction(function () use ($data, $quantityAvailable): PlantSpecies {
            $plant = PlantSpecies::create($data);

            PlantInventory::create([
                'plant_species_id' => $plant->id,
                'quantity_available' => $quantityAvailable,
            ]);

            return $plant->load('inventory');
        });
    }

    public function updatePlantSpecies(
        int|string $id,
        array $data
    ): PlantSpecies {
        return DB::transaction(function () use ($id, $data): PlantSpecies {
            $plant = PlantSpecies::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $quantityAvailable = $data['quantity_available'] ?? null;
            unset($data['quantity_available']);
            $plant->update($data);

            if ($quantityAvailable !== null) {
                $plant->inventory()->updateOrCreate(
                    ['plant_species_id' => $plant->id],
                    ['quantity_available' => $quantityAvailable]
                );
            }

            return $plant->load('inventory');
        });
    }

    public function findForEdit(int|string $id): PlantSpecies
    {
        return PlantSpecies::with('inventory')->findOrFail($id);
    }

    public function deletePlantSpecies(int|string $id): void
    {
        DB::transaction(function () use ($id): void {
            $plant = PlantSpecies::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $affectedSaleIds = $plant->saleItems()
                ->pluck('nursery_sale_id')
                ->unique();

            \App\Models\NurserySale::whereKey($affectedSaleIds)->delete();
            $plant->inventory()->delete();
            $plant->delete();
        });
    }

}
