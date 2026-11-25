<?php

namespace App\Http\Requests;

use App\Models\PlantSpecies;
use Illuminate\Validation\Rule;

class UpdatePlantSpeciesRequest extends PlantSpeciesRequest
{
    public function rules(): array
    {
        $id = $this->route('plant_species');

        return [
            'plant_name' => ['required', 'string', 'max:150'],
            'botanical_name' => [
                'required',
                'string',
                'max:200',
                Rule::unique('plant_species', 'botanical_name')->ignore($id),
            ],
            'pot_size' => ['required', 'string', Rule::in(PlantSpecies::POT_SIZES)],
            'unit_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'quantity_available' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'plant_name.required' => 'The plant name is required.',
            'botanical_name.required' => 'The botanical name is required.',
            'botanical_name.unique' => 'This botanical name is already registered.',
            'pot_size.in' => 'Select one of the available pot sizes.',
            'unit_price.min' => 'The unit price must be greater than zero.',
            'unit_price.max' => 'The unit price is too large.',
            'quantity_available.integer' => 'The available quantity must be a whole number.',
            'quantity_available.min' => 'The available quantity cannot be negative.',
            'quantity_available.max' => 'The available quantity cannot exceed 100 plants.',
        ];
    }
}
