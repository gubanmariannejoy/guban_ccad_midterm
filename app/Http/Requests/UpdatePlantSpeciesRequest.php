<?php

namespace App\Http\Requests;

use App\Models\PlantSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlantSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['plant_name', 'botanical_name', 'pot_size'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => trim($value)]);
            }
        }

        $botanicalName = $this->input('botanical_name');

        if (is_string($botanicalName) && $botanicalName !== '') {
            $this->merge([
                'botanical_name' => mb_convert_case(
                    mb_strtolower(strip_tags($botanicalName)),
                    MB_CASE_TITLE,
                    'UTF-8'
                ),
            ]);
        }

    }

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
