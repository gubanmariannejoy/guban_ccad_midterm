<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class PlantSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['plant_name', 'botanical_name', 'pot_size'] as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = strip_tags($value);
            $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value) ?? $value;
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
            $value = trim($value);

            if (in_array($field, ['plant_name', 'botanical_name'], true) && $value !== '') {
                $value = mb_convert_case(
                    mb_strtolower($value),
                    MB_CASE_TITLE,
                    'UTF-8'
                );
            }

            $normalized[$field] = $value;
        }

        $this->merge($normalized);
    }
}
