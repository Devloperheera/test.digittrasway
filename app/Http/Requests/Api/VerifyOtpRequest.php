<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            ],
            'otp' => [
                'required',
                'string',
                'digits:4',  // ✅ Changed from 6 to 4
                'regex:/^\d{4}$/'  // ✅ Changed from 6 to 4
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
            'contact_number.regex' => 'Contact number must start with 6, 7, 8, or 9',

            'otp.required' => 'OTP is required',
            'otp.string' => 'OTP must be a string',
            'otp.digits' => 'OTP must be exactly 4 digits',  // ✅ Changed message
            'otp.regex' => 'OTP must be a 4-digit number'  // ✅ Changed message
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contact_number' => 'mobile number',
            'otp' => 'verification code'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional custom validation if needed
            $otp = $this->input('otp');

            // Ensure OTP is numeric
            if ($otp && !ctype_digit($otp)) {
                $validator->errors()->add('otp', 'OTP must contain only numbers');
            }

            // Ensure OTP length is exactly 4
            if ($otp && strlen($otp) !== 4) {
                $validator->errors()->add('otp', 'OTP must be exactly 4 digits');
            }
        });
    }
}
