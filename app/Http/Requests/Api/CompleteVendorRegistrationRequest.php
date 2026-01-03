<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CompleteVendorRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Personal Details - Required
            'name' => 'required|string|min:2|max:255|regex:/^[a-zA-Z\s\.]+$/',
            'email' => 'nullable|email|max:255',
            'dob' => 'required|date|before:today|after:1900-01-01',
            'gender' => 'required|in:male,female,other',
            'emergency_contact' => 'nullable|string|digits:10',

            // Password - Made Optional
            'password' => 'nullable|string|min:6|max:255',

            // Documents - Required
            'aadhar_number' => 'required|string|size:12|regex:/^\d{12}$/',
            'aadhar_front' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            'aadhar_back' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            'pan_number' => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'pan_image' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',

            // RC Documents - Required for Vendors ✅
            'rc_number' => 'required|string|min:10|max:20|regex:/^[A-Z0-9]+$/',
            'rc_image' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',

            // Address - Required
            'full_address' => 'required|string|min:10|max:500',
            'state' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'pincode' => 'required|string|size:6|regex:/^\d{6}$/',
            'country' => 'nullable|string|max:100',

            // Bank Details - Required
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|min:9|max:20|regex:/^\d+$/',
            'ifsc' => 'required|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            'postal_code' => 'nullable|string|max:10',

            // Flags - Required
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
            'dob.required' => 'Date of birth is required',
            'gender.required' => 'Gender is required',

            // Password - Removed required message, kept min length
            'password.min' => 'Password must be at least 6 characters',

            // Documents
            'aadhar_number.required' => 'Aadhaar number is required',
            'aadhar_number.size' => 'Aadhaar number must be exactly 12 digits',
            'pan_number.required' => 'PAN number is required',
            'pan_number.size' => 'PAN number must be exactly 10 characters',

            // RC Documents ✅
            'rc_number.required' => 'RC number is required',
            'rc_number.regex' => 'Invalid RC number format',
            'rc_image.image' => 'RC image must be an image file',
            'rc_image.max' => 'RC image must not exceed 2MB',

            // Address
            'full_address.required' => 'Full address is required',
            'state.required' => 'State is required',
            'city.required' => 'City is required',
            'pincode.required' => 'Pincode is required',

            // Bank Details
            'bank_name.required' => 'Bank name is required',
            'account_number.required' => 'Bank account number is required',
            'ifsc.required' => 'IFSC code is required',

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
            $this->merge(['name' => $cleanName]);
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

        // Clean RC number ✅
        if ($this->has('rc_number')) {
            $this->merge([
                'rc_number' => strtoupper(trim($this->rc_number))
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
