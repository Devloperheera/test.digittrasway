<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'contact_number' => [
                'required',
                'string',
                'digits:10',
                'regex:/^[6-9]\d{9}$/'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contact_number.required' => 'Contact number is required',
            'contact_number.string' => 'Contact number must be a string',
            'contact_number.digits' => 'Contact number must be exactly 10 digits',
            'contact_number.regex' => 'Contact number must start with 6, 7, 8, or 9'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contact_number' => 'mobile number'
        ];
    }

    /**
     * Prepare the data for validation.
     * Cleans and normalizes contact number before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('contact_number')) {
            // Remove all non-digit characters
            $contactNumber = preg_replace('/\D/', '', $this->contact_number);

            // Handle +91 country code (13 digits total)
            if (strlen($contactNumber) === 12 && str_starts_with($contactNumber, '91')) {
                $contactNumber = substr($contactNumber, 2);
            }

            // Handle 91 country code without + (12 digits total)
            if (strlen($contactNumber) === 12 && str_starts_with($contactNumber, '91')) {
                $contactNumber = substr($contactNumber, 2);
            }

            // Handle +91 with extra digit (13 digits)
            if (strlen($contactNumber) === 13 && str_starts_with($contactNumber, '91')) {
                $contactNumber = substr($contactNumber, 2);
            }

            // Merge cleaned number back
            $this->merge([
                'contact_number' => $contactNumber
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $contactNumber = $this->input('contact_number');

            // Additional validation: Check if number exists (optional)
            if ($contactNumber) {
                // Ensure it's numeric after cleaning
                if (!ctype_digit($contactNumber)) {
                    $validator->errors()->add('contact_number', 'Contact number must contain only digits');
                }

                // Ensure length is exactly 10 after cleaning
                if (strlen($contactNumber) !== 10) {
                    $validator->errors()->add('contact_number', 'Contact number must be exactly 10 digits after cleaning');
                }
            }
        });
    }
}
