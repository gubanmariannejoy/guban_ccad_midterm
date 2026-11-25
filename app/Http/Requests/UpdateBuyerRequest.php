<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'email'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => trim($value)]);
            }
        }

        $contact = $this->input('contact_no');

        if (is_string($contact)) {
            $digits = preg_replace('/\D+/', '', $contact) ?? '';

            if (str_starts_with($digits, '63') && strlen($digits) === 12) {
                $contact = '0'.substr($digits, 2);
            } elseif (str_starts_with($digits, '09') && strlen($digits) === 11) {
                $contact = $digits;
            } elseif (trim($contact) === '') {
                $contact = null;
            } else {
                $contact = $digits;
            }

            $this->merge(['contact_no' => $contact]);
        }
    }

    public function rules(): array
    {
        $buyerId = $this->route('buyer');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($buyerId),
            ],
            'contact_no' => [
                'nullable',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_no.regex' => 'Enter a valid Philippine mobile number starting with 09 and containing 11 digits.',
        ];
    }
}
