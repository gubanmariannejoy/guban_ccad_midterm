<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
        return [
            'plant_name' => ['required', 'string', 'max:150'],
            'botanical_name' => [
                'required',
                'string',
                'max:200',
                Rule::unique('plant_species', 'botanical_name'),
            ],
            'pot_size' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'plant_name.required' => 'The plant name is required.',
            'botanical_name.required' => 'The botanical name is required.',
            'botanical_name.unique' => 'This botanical name already exists.',
            'unit_price.min' => 'The unit price must be greater than zero.',
            'unit_price.max' => 'The unit price is too large.',
        ];
    }
}
