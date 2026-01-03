<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
}
