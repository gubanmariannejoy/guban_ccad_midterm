<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuyerCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'regex:/^\+63[0-9]{10}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash'])],
            'dispatch_date' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:2027-12-31'],
        ];
    }
}
