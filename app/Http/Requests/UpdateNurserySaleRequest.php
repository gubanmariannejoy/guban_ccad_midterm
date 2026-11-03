<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNurserySaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'buyer_id' => ['sometimes', 'required', 'integer', 'exists:buyers,id'],

            'payment_method' => [
                'sometimes',
                'required',
                Rule::in(['Cash', 'GCash', 'Bank Transfer']),
            ],

            'discount_rate' => ['sometimes', 'required', 'numeric', 'between:0,1'],

            'dispatch_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],

            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*' => ['required_with:items', 'array:plant_species_id,quantity_bought'],

            'items.*.plant_species_id' => [
                'required_with:items',
                'integer',
                'distinct',
                'exists:plant_species,id',
            ],

            'items.*.quantity_bought' => [
                'required_with:items',
                'integer',
                'min:1',
                'max:100000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'buyer_id.exists' => 'The selected buyer does not exist.',
            'discount_rate.between' => 'The discount must be between 0 and 1.',
            'payment_method.in' => 'Select a supported payment method.',
            'dispatch_date.after_or_equal' => 'The dispatch date cannot be in the past.',
            'items.min' => 'A sale must contain at least one plant.',
            'items.*.plant_species_id.distinct' => 'A plant can only be listed once per sale.',
            'items.*.quantity_bought.min' => 'Quantity must be at least one.',
            'items.*.quantity_bought.max' => 'Quantity cannot exceed 100,000.',
        ];
    }
}
