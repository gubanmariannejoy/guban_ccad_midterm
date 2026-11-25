<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $contact = $this->input('contact_no');

        if (is_string($contact)) {
            $digits = preg_replace('/\D+/', '', $contact) ?? '';

            if (str_starts_with($digits, '63') && strlen($digits) === 12) {
                $digits = '0'.substr($digits, 2);
            }

            $this->merge(['contact_no' => $digits]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'contact_no' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_no.regex' => 'Enter a valid Philippine mobile number starting with 09 and containing 11 digits.',
        ];
    }
}
