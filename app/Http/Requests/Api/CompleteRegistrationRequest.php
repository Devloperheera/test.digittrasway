<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Personal Details
            'name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s\.]+$/',
            'email' => 'nullable|email|max:255',
            'dob' => 'required|date|before:today|after:1900-01-01',
            'gender' => 'required|in:male,female,other',
            'emergency_contact' => 'nullable|string|digits:10',

            // Password - Now Optional
            'password' => 'nullable|string|min:6|max:255',

            // Documents
            'aadhar_number' => 'required|string|size:12|regex:/^\d{12}$/',
            'aadhar_front' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            'aadhar_back' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            'pan_number' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'pan_image' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',

            // Address
            'full_address' => 'required|string|min:10|max:500',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'pincode' => 'required|string|size:6|regex:/^\d{6}$/',
            'country' => 'nullable|string|max:100',

            // Bank Details
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|min:9|max:20|regex:/^\d+$/',
            'ifsc' => 'required|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'postal_code' => 'nullable|string|max:10',

            // Flags
            'declaration' => 'required',
            'same_address' => 'nullable'
        ];
    }

    public function messages(): array
    {
        return [
            // Personal Details
            'name.required' => 'Full name is required',
            'name.regex' => 'Name should contain only letters, spaces, and dots',
            'name.min' => 'Name must be at least 2 characters',
            'dob.required' => 'Date of birth is required',
            'dob.before' => 'Date of birth must be before today',
            'dob.after' => 'Invalid date of birth',
            'gender.required' => 'Gender is required',
            'emergency_contact.digits' => 'Emergency contact must be 10 digits',

            // Password (Removed required message, only min)
            'password.min' => 'Password must be at least 6 characters',

            // Documents
            'aadhar_number.required' => 'Aadhaar number is required',
            'aadhar_number.size' => 'Aadhaar number must be exactly 12 digits',
            'aadhar_number.regex' => 'Aadhaar number must contain only digits',
            'pan_number.required' => 'PAN number is required',
            'pan_number.size' => 'PAN number must be exactly 10 characters',
            'pan_number.regex' => 'Invalid PAN number format (e.g., ABCDE1234F)',

            // File uploads
            'aadhar_front.image' => 'Aadhaar front must be an image',
            'aadhar_front.max' => 'Aadhaar front image must not exceed 2MB',
            'aadhar_back.image' => 'Aadhaar back must be an image',
            'aadhar_back.max' => 'Aadhaar back image must not exceed 2MB',
            'pan_image.image' => 'PAN image must be an image',
            'pan_image.max' => 'PAN image must not exceed 2MB',

            // Address
            'full_address.required' => 'Full address is required',
            'full_address.min' => 'Address must be at least 10 characters',
            'state.required' => 'State is required',
            'city.required' => 'City is required',
            'pincode.required' => 'Pincode is required',
            'pincode.size' => 'Pincode must be exactly 6 digits',
            'pincode.regex' => 'Invalid pincode format',

            // Bank Details
            'bank_name.required' => 'Bank name is required',
            'account_number.required' => 'Bank account number is required',
            'account_number.min' => 'Account number must be at least 9 digits',
            'account_number.regex' => 'Account number must contain only digits',
            'ifsc.required' => 'IFSC code is required',
            'ifsc.size' => 'IFSC code must be exactly 11 characters',
            'ifsc.regex' => 'Invalid IFSC code format (e.g., HDFC0000123)',

            // Flags
            'declaration.required' => 'Declaration acceptance is required'
        ];
    }

    protected function prepareForValidation()
    {
        // Clean and prepare data
        if ($this->has('name')) {
            $cleanName = trim($this->name);
            $cleanName = preg_replace('/\s+/', ' ', $cleanName);
            $this->merge([
                'name' => $cleanName
            ]);
        }

        if ($this->has('aadhar_number')) {
            $this->merge([
                'aadhar_number' => preg_replace('/\D/', '', $this->aadhar_number)
            ]);
        }

        if ($this->has('pan_number')) {
            $this->merge([
                'pan_number' => strtoupper(trim($this->pan_number))
            ]);
        }

        if ($this->has('pincode')) {
            $this->merge([
                'pincode' => preg_replace('/\D/', '', $this->pincode)
            ]);
        }

        if ($this->has('ifsc')) {
            $this->merge([
                'ifsc' => strtoupper(trim($this->ifsc))
            ]);
        }

        // Convert string boolean values to actual booleans
        if ($this->has('declaration')) {
            $declaration = $this->declaration;
            if (is_string($declaration)) {
                $declaration = filter_var($declaration, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($declaration === null) {
                    $declaration = ($this->declaration === 'true' || $this->declaration === '1');
                }
            }
            $this->merge(['declaration' => $declaration]);
        }

        if ($this->has('same_address')) {
            $sameAddress = $this->same_address;
            if (is_string($sameAddress)) {
                $sameAddress = filter_var($sameAddress, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($sameAddress === null) {
                    $sameAddress = ($this->same_address === 'true' || $this->same_address === '1');
                }
            }
            $this->merge(['same_address' => $sameAddress]);
        }
    }
}
