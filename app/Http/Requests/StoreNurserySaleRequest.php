<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNurserySaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'buyer_id' => ['required', 'integer', 'exists:buyers,id'],

            'payment_method' => [
                'required',
                Rule::in(['Cash', 'GCash', 'Bank Transfer']),
            ],

            'discount_rate' => 'required|numeric|between:0,1',

            'dispatch_date' => ['nullable', 'date', 'after_or_equal:today'],

            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:plant_species_id,quantity_bought'],

            'items.*.plant_species_id' => [
                'required',
                'integer',
                'distinct',
                'exists:plant_species,id',
            ],

            'items.*.quantity_bought' => [
                'required',
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
            'payment_method.in' => 'Select a supported payment method.',
            'discount_rate.between' => 'The discount must be between 0 and 1.',
            'dispatch_date.after_or_equal' => 'The dispatch date cannot be in the past.',
            'items.required' => 'At least one plant must be selected.',
            'items.min' => 'A sale must contain at least one plant.',
            'items.*.plant_species_id.distinct' => 'A plant can only be listed once per sale.',
            'items.*.quantity_bought.min' => 'Quantity must be at least one.',
            'items.*.quantity_bought.max' => 'Quantity cannot exceed 100,000.',
            'items.*.plant_species_id.exists' => 'One or more selected plants do not exist.',
        ];
    }
}
