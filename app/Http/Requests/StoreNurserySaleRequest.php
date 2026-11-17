<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNurserySaleRequest extends FormRequest
{
    public function authorize(): bool
    {
       return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required_without:new_buyer', 'nullable', 'integer', 'exists:users,id'],
            'new_buyer' => ['required_without:user_id', 'array:name,email,contact_no,password'],
            'new_buyer.name' => ['required_with:new_buyer', 'string', 'max:150'],
            'new_buyer.email' => ['required_with:new_buyer', 'email', 'max:255', 'unique:users,email'],
            'new_buyer.contact_no' => ['required_with:new_buyer', 'string', 'regex:/^[0-9]{11}$/'],
            'new_buyer.password' => ['required_with:new_buyer', 'string', 'min:8'],

            'payment_method' => [
                'required',
                Rule::in(['Cash', 'GCash', 'Bank Transfer']),
            ],

            'discount_rate' => 'required|numeric|between:0,1',

            'dispatch_date' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:2029-12-31'],

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
            'user_id.exists' => 'The selected buyer account does not exist.',
            'new_buyer.name.required_with' => 'Enter the buyer name.',
            'new_buyer.email.required_with' => 'Enter the buyer email address.',
            'new_buyer.email.unique' => 'An account already uses this email address.',
            'new_buyer.contact_no.required_with' => 'Enter the buyer contact number.',
            'new_buyer.contact_no.regex' => 'The buyer contact number must contain exactly 11 digits.',
            'new_buyer.password.min' => 'The buyer password must contain at least 8 characters.',
            'payment_method.in' => 'Select a supported payment method.',
            'discount_rate.between' => 'The discount must be between 0 and 1.',
            'dispatch_date.after_or_equal' => 'The dispatch date cannot be in the past.',
            'dispatch_date.before_or_equal' => 'The dispatch date cannot be after December 31, 2029.',
            'items.required' => 'At least one plant must be selected.',
            'items.min' => 'A sale must contain at least one plant.',
            'items.*.plant_species_id.distinct' => 'A plant can only be listed once per sale.',
            'items.*.quantity_bought.min' => 'Quantity must be at least one.',
            'items.*.quantity_bought.max' => 'Quantity cannot exceed 100,000.',
            'items.*.plant_species_id.exists' => 'One or more selected plants do not exist.',
        ];
    }
}
