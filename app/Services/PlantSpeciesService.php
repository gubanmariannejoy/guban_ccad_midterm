<?php

namespace App\Services;

use App\Models\PlantInventory;
use App\Models\PlantSpecies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlantSpeciesService
{
    public function createPlantSpecies(array $data): PlantSpecies
    {
        $data = $this->validatePlantSpeciesData($data);

        return DB::transaction(function () use ($data) {
            $plant = PlantSpecies::create($data);

            PlantInventory::create([
                'plant_species_id' => $plant->id,
                'quantity_available' => 0,
            ]);

            return $plant;
        });
    }

    public function updatePlantSpecies(
        int|string $id,
        array $data
    ): PlantSpecies {
        $plant = PlantSpecies::findOrFail($id);
        $data = $this->validatePlantSpeciesData($data, $plant);

        $plant->update($data);

        return $plant->refresh();
    }

    public function findForEdit(int|string $id): PlantSpecies
    {
        return PlantSpecies::findOrFail($id);
    }

    public function deletePlantSpecies(int|string $id): void
    {
        DB::transaction(function () use ($id): void {
            $plant = PlantSpecies::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($plant->saleItems()->exists()) {
                throw ValidationException::withMessages([
                    'plant' => 'This plant cannot be deleted because it is included in sales.',
                ]);
            }

            $plant->delete();
        });
    }

    private function titleCase(string $value): string
    {
        return mb_convert_case(
            mb_strtolower(trim($value)),
            MB_CASE_TITLE,
            'UTF-8'
        );
    }

    private function validatePlantSpeciesData(
        array $data,
        ?PlantSpecies $plant = null
    ): array {
        $data = array_intersect_key($data, array_flip([
            'plant_name',
            'botanical_name',
            'pot_size',
            'unit_price',
        ]));

        foreach (['plant_name', 'botanical_name', 'pot_size'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = $this->sanitizeText($data[$field]);
            }
        }

        $data['plant_name'] = is_string($data['plant_name'] ?? null)
            ? $this->titleCase($data['plant_name'])
            : null;
        $data['botanical_name'] = is_string($data['botanical_name'] ?? null)
            ? $this->titleCase($data['botanical_name'])
            : null;

        return Validator::make($data, [
            'plant_name' => ['required', 'string', 'max:150'],
            'botanical_name' => [
                'required',
                'string',
                'max:200',
                Rule::unique('plant_species', 'botanical_name')
                    ->ignore($plant?->id),
            ],
            'pot_size' => ['required', 'string', 'max:50'],
            'unit_price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
        ])->validate();
    }

    private function sanitizeText(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value ?? '');
    }
}
