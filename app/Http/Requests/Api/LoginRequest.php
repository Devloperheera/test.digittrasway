<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_number' => [
                'required',
                'string',
                'digits:10',
                'regex:/^[6-9]\d{9}$/'
            ],
            // Password is now OPTIONAL (not required!)
            'password' => [
                'nullable',
                'string',
                'min:6'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'contact_number.required' => 'Contact number is required',
            'contact_number.digits' => 'Contact number must be exactly 10 digits',
            'contact_number.regex' => 'Please enter a valid Indian mobile number',
            'password.min' => 'Password must be at least 6 characters'
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('contact_number')) {
            // Clean contact number
            $contactNumber = preg_replace('/\D/', '', $this->contact_number);

            // Remove country code if present
            if (strlen($contactNumber) == 13 && str_starts_with($contactNumber, '91')) {
                $contactNumber = substr($contactNumber, 2);
            }

            $this->merge([
                'contact_number' => $contactNumber
            ]);
        }
    }
}
