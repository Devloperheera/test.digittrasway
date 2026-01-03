<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentVerificationService
{
    private $surepassToken;
    private $baseUrl;

    public function __construct()
    {
        $this->surepassToken = env('SUREPASS_TOKEN');
        $this->baseUrl = env('SUREPASS_BASE_URL', 'https://kyc-api.surepass.app/api/v1');
    }

    /**
     * ✅ VERIFY BANK ACCOUNT (WITHOUT NAME REQUIRED)
     */
    public function verifyBankAccount($accountNumber, $ifsc, $accountHolderName = null)
    {
        try {
            Log::info('🏦 Bank Verification Started', [
                'account_number' => $accountNumber,
                'ifsc' => $ifsc
            ]);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/bank-verification/', [
                'id_number' => $accountNumber,
                'ifsc' => strtoupper($ifsc),
                'ifsc_details' => true
            ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('📡 Bank Verification API Response', [
                'status_code' => $statusCode,
                'success' => $responseData['success'] ?? false,
                'response' => $responseData
            ]);

            // ❌ API Failed
            if ($statusCode !== 200 || !($responseData['success'] ?? false)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => $responseData['message'] ?? 'Bank verification failed',
                    'error' => $responseData
                ];
            }

            $data = $responseData['data'] ?? [];

            // ❌ Check if account exists
            if (!($data['account_exists'] ?? false)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Bank account does not exist',
                    'details' => [
                        'account_number' => $accountNumber,
                        'ifsc' => $ifsc
                    ]
                ];
            }

            $bankAccountName = $data['full_name'] ?? null;

            // ❌ No account holder name found
            if (empty($bankAccountName)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Account holder name not found',
                    'details' => [
                        'account_number' => $accountNumber,
                        'ifsc' => $ifsc
                    ]
                ];
            }

            // ✅ SUCCESS - Account verified (name matching only if provided)
            $verifiedData = [
                'account_number' => $accountNumber,
                'ifsc' => strtoupper($ifsc),
                'account_holder_name' => $bankAccountName,
                'account_exists' => true,
                'upi_id' => $data['upi_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'imps_ref_no' => $data['imps_ref_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'ifsc_details' => $data['ifsc_details'] ?? null,
                'verified_at' => now()->toDateTimeString(),
                'verification_method' => 'surepass_api',
                'bank_verified' => true
            ];

            Log::info('✅ Bank Account Verified Successfully', [
                'account_number' => $accountNumber,
                'holder_name' => $bankAccountName
            ]);

            return [
                'success' => true,
                'verified' => true,
                'message' => 'Bank account verified successfully',
                'data' => $verifiedData
            ];

        } catch (\Exception $e) {
            Log::error('💥 Bank Verification Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'verified' => false,
                'message' => 'Bank verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ============================================
     * ✅ VERIFY RC V2 - NEW SUREPASS API (With Enrich)
     * Endpoint: /rc/rc-v2
     * ============================================
     */
    public function verifyRcV2($rcNumber, $ownerName = null)
    {
        try {
            Log::info('🚗 RC V2 Verification Started', [
                'rc_number' => $rcNumber,
                'owner_name' => $ownerName
            ]);

            // ✅ Call SurePass RC V2 API with enrich parameter
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/rc/rc-v2', [
                'id_number' => strtoupper($rcNumber),
                'enrich' => true
            ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('📡 RC V2 API Response', [
                'status_code' => $statusCode,
                'success' => $responseData['success'] ?? false,
                'rc_number' => $rcNumber
            ]);

            // ❌ API Call Failed
            if ($statusCode !== 200 || !($responseData['success'] ?? false)) {
                Log::error('❌ RC V2 API Call Failed', [
                    'status_code' => $statusCode,
                    'message' => $responseData['message'] ?? 'Unknown error',
                    'rc_number' => $rcNumber
                ]);

                return [
                    'success' => false,
                    'verified' => false,
                    'message' => $responseData['message'] ?? 'RC verification failed',
                    'error_code' => $responseData['error_code'] ?? 'RC_API_ERROR',
                    'error' => $responseData
                ];
            }

            $data = $responseData['data'] ?? [];

            // ❌ Check if RC data exists
            if (empty($data)) {
                Log::warning('⚠️ RC Data Empty', [
                    'rc_number' => $rcNumber
                ]);

                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Invalid RC number. No RC details found.'
                ];
            }

            $rcOwnerName = $data['owner_name'] ?? $data['registered_owner_name'] ?? null;

            // ❌ No owner name found
            if (empty($rcOwnerName)) {
                Log::warning('⚠️ RC Owner Name Not Found', [
                    'rc_number' => $rcNumber
                ]);

                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Invalid RC number. No owner details found.'
                ];
            }

            // ✅ Name matching (if owner name provided)
            $nameMatch = true;
            if ($ownerName) {
                $nameMatch = $this->matchNames($ownerName, $rcOwnerName);

                if (!$nameMatch) {
                    Log::warning('⚠️ RC Owner Name Mismatch', [
                        'provided_name' => $ownerName,
                        'rc_owner_name' => $rcOwnerName,
                        'rc_number' => $rcNumber
                    ]);

                    return [
                        'success' => true,
                        'verified' => false,
                        'name_match' => false,
                        'message' => 'Name mismatch. RC owner name does not match.',
                        'details' => [
                            'provided_name' => $ownerName,
                            'rc_owner_name' => $rcOwnerName
                        ],
                        'data' => $data
                    ];
                }
            }

            // ✅ RC VERIFIED SUCCESSFULLY
            $verifiedData = [
                'rc_number' => $data['registration_number'] ?? $data['rc_number'] ?? $rcNumber,
                'owner_name' => $rcOwnerName,
                'father_name' => $data['father_name'] ?? null,
                'vehicle_class' => $data['vehicle_class'] ?? null,
                'vehicle_class_desc' => $data['vehicle_class_desc'] ?? $data['vehicle_class_description'] ?? null,
                'fuel_type' => $data['fuel_type'] ?? null,
                'vehicle_category' => $data['vehicle_category'] ?? null,
                'vehicle_category_description' => $data['vehicle_category_description'] ?? null,
                'registration_date' => $data['registration_date'] ?? null,
                'registration_validity_to' => $data['registration_validity_to'] ?? null,
                'vehicle_manufacturer' => $data['vehicle_manufacturer'] ?? $data['maker_model'] ?? null,
                'maker_model' => $data['maker_model'] ?? null,
                'chassis_number' => $data['chassis_number'] ?? null,
                'engine_number' => $data['engine_number'] ?? null,
                'manufacturing_date' => $data['manufacturing_date'] ?? null,
                'seating_capacity' => $data['seating_capacity'] ?? null,
                'vehicle_color' => $data['vehicle_color'] ?? null,
                'vehicle_weight' => $data['vehicle_weight'] ?? $data['unladen_weight'] ?? null,
                'cubic_capacity' => $data['cubic_capacity'] ?? null,
                'vehicle_insurance_company' => $data['vehicle_insurance_company'] ?? null,
                'insurance_policy_number' => $data['insurance_policy_number'] ?? null,
                'insurance_upto_date' => $data['insurance_upto_date'] ?? null,
                'address' => $data['address'] ?? null,
                'state' => $data['state'] ?? null,
                'city' => $data['city'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'norms_type' => $data['norms_type'] ?? null,
                'tax_validity_to' => $data['tax_validity_to'] ?? null,
                'permit_validity_to' => $data['permit_validity_to'] ?? null,
                'verified_at' => now()->toDateTimeString(),
                'verification_method' => 'surepass_api_v2',
                'government_verified' => true
            ];

            Log::info('✅ RC V2 Verified Successfully', [
                'rc_number' => $rcNumber,
                'owner_name' => $rcOwnerName,
                'vehicle_class' => $verifiedData['vehicle_class'] ?? null
            ]);

            return [
                'success' => true,
                'verified' => true,
                'name_match' => $nameMatch,
                'message' => 'RC verified successfully',
                'data' => $verifiedData
            ];

        } catch (\Exception $e) {
            Log::error('💥 RC V2 Verification Exception', [
                'error' => $e->getMessage(),
                'rc_number' => $rcNumber,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'verified' => false,
                'message' => 'RC verification failed: ' . $e->getMessage(),
                'error_code' => 'RC_API_EXCEPTION'
            ];
        }
    }

    /**
     * ============================================
     * ✅ VERIFY RC (OLD METHOD - Keep for backward compatibility)
     * ============================================
     */
    public function verifyRc($rcNumber, $ownerName = null)
    {
        try {
            Log::info('RC Verification Started (Old Method)', [
                'rc_number' => $rcNumber,
                'owner_name' => $ownerName
            ]);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/rc/rc-full', [
                'id_number' => strtoupper($rcNumber)
            ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('RC API Response', [
                'status_code' => $statusCode,
                'success' => $responseData['success'] ?? false
            ]);

            if ($statusCode !== 200 || !($responseData['success'] ?? false)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => $responseData['message'] ?? 'RC verification failed',
                    'error' => $responseData
                ];
            }

            $data = $responseData['data'] ?? [];

            if (empty($data['owner_name'])) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Invalid RC number. No owner details found.',
                    'error' => $responseData
                ];
            }

            $rcOwnerName = $data['owner_name'];
            $nameMatch = true;

            if ($ownerName) {
                $nameMatch = $this->matchNames($ownerName, $rcOwnerName);

                if (!$nameMatch) {
                    return [
                        'success' => false,
                        'verified' => false,
                        'name_match' => false,
                        'message' => 'Name mismatch. RC owner name does not match.',
                        'details' => [
                            'provided_name' => $ownerName,
                            'rc_owner_name' => $rcOwnerName
                        ]
                    ];
                }
            }

            $verifiedData = [
                'rc_number' => $data['rc_number'] ?? $rcNumber,
                'owner_name' => $rcOwnerName,
                'vehicle_category' => $data['vehicle_category_description'] ?? null,
                'maker_model' => $data['maker_model'] ?? null,
                'registration_date' => $data['registration_date'] ?? null,
                'verified_at' => now()->toDateTimeString(),
                'verification_method' => 'surepass_api',
                'government_verified' => true
            ];

            return [
                'success' => true,
                'verified' => true,
                'name_match' => $nameMatch,
                'message' => 'RC verified successfully',
                'data' => $verifiedData
            ];

        } catch (\Exception $e) {
            Log::error('RC Verification Exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'verified' => false,
                'message' => 'RC verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify Aadhaar
     */
    public function verifyAadhaar($aadhaarNumber, $name)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/aadhaar-v2/generate-otp', [
                'id_number' => $aadhaarNumber
            ]);

            $responseData = $response->json();

            if ($response->status() === 200 && ($responseData['success'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Aadhaar OTP sent successfully',
                    'data' => $responseData['data'] ?? []
                ];
            }

            return ['success' => false, 'message' => 'Aadhaar verification failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Aadhaar verification failed: ' . $e->getMessage()];
        }
    }

    /**
     * Verify PAN
     */
    public function verifyPan($panNumber, $name)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/pan/pan', [
                'id_number' => strtoupper($panNumber)
            ]);

            $responseData = $response->json();

            if ($response->status() === 200 && ($responseData['success'] ?? false)) {
                return [
                    'success' => true,
                    'verified' => true,
                    'message' => 'PAN verified successfully',
                    'data' => $responseData['data'] ?? []
                ];
            }

            return ['success' => false, 'message' => 'PAN verification failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'PAN verification failed: ' . $e->getMessage()];
        }
    }

    /**
     * ============================================
     * ✅ VERIFY DL V2 - NEW SUREPASS API (With Enrich)
     * Endpoint: /driving-license/driving-license-v2
     * ============================================
     */
    public function verifyDlV2($dlNumber, $dob, $name = null)
    {
        try {
            Log::info('🚗 DL V2 Verification Started', [
                'dl_number' => $dlNumber,
                'dob' => $dob,
                'name' => $name
            ]);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/driving-license/driving-license-v2', [
                'id_number' => strtoupper($dlNumber),
                'dob' => $dob,
                'enrich' => true
            ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('📡 DL V2 API Response', [
                'status_code' => $statusCode,
                'success' => $responseData['success'] ?? false
            ]);

            if ($statusCode !== 200 || !($responseData['success'] ?? false)) {
                Log::error('❌ DL V2 API Call Failed', [
                    'status_code' => $statusCode,
                    'message' => $responseData['message'] ?? 'Unknown error',
                    'dl_number' => $dlNumber
                ]);

                return [
                    'success' => false,
                    'verified' => false,
                    'message' => $responseData['message'] ?? 'DL verification failed',
                    'error_code' => $responseData['error_code'] ?? 'DL_API_ERROR'
                ];
            }

            $data = $responseData['data'] ?? [];

            // ❌ Check if DL data exists
            if (empty($data)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Invalid DL number. No DL details found.'
                ];
            }

            $dlOwnerName = $data['name'] ?? $data['holder_name'] ?? null;
            $dlDob = $data['dob'] ?? null;

            // ❌ No owner name found
            if (empty($dlOwnerName)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Invalid DL number. No license holder details found.'
                ];
            }

            // ❌ DOB mismatch
            if (!empty($dlDob) && $dob !== $dlDob) {
                Log::warning('⚠️ DL DOB Mismatch', [
                    'provided_dob' => $dob,
                    'dl_dob' => $dlDob,
                    'dl_number' => $dlNumber
                ]);

                return [
                    'success' => true,
                    'verified' => false,
                    'dob_match' => false,
                    'message' => 'Date of birth mismatch.',
                    'details' => [
                        'provided_dob' => $dob,
                        'dl_dob' => $dlDob
                    ],
                    'data' => $data
                ];
            }

            // ✅ Name matching (if provided)
            $nameMatch = true;
            if ($name) {
                $nameMatch = $this->matchNames($name, $dlOwnerName);

                if (!$nameMatch) {
                    Log::warning('⚠️ DL Name Mismatch', [
                        'provided_name' => $name,
                        'dl_owner_name' => $dlOwnerName,
                        'dl_number' => $dlNumber
                    ]);

                    return [
                        'success' => true,
                        'verified' => false,
                        'name_match' => false,
                        'message' => 'Name mismatch.',
                        'details' => [
                            'provided_name' => $name,
                            'dl_owner_name' => $dlOwnerName
                        ],
                        'data' => $data
                    ];
                }
            }

            // ✅ DL VERIFIED SUCCESSFULLY
            $verifiedData = [
                'dl_number' => $data['license_number'] ?? $data['dl_number'] ?? $dlNumber,
                'name' => $dlOwnerName,
                'father_or_husband_name' => $data['father_or_husband_name'] ?? null,
                'dob' => $dlDob,
                'gender' => $data['gender'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'state' => $data['state'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'address' => $data['address'] ?? null,
                'doi' => $data['doi'] ?? null,
                'doe' => $data['doe'] ?? null,
                'issue_date' => $data['doi'] ?? $data['issue_date'] ?? null,
                'expiry_date' => $data['doe'] ?? $data['expiry_date'] ?? null,
                'vehicle_classes' => $data['vehicle_classes'] ?? $data['classes'] ?? [],
                'aadhaar_number' => $data['aadhaar_number'] ?? null,
                'verified_at' => now()->toDateTimeString(),
                'verification_method' => 'surepass_api_v2',
                'government_verified' => true
            ];

            Log::info('✅ DL V2 Verified Successfully', [
                'dl_number' => $dlNumber,
                'name' => $dlOwnerName,
                'dob' => $dlDob
            ]);

            return [
                'success' => true,
                'verified' => true,
                'dob_match' => true,
                'name_match' => $nameMatch,
                'message' => 'DL verified successfully',
                'data' => $verifiedData
            ];

        } catch (\Exception $e) {
            Log::error('💥 DL V2 Verification Exception', [
                'error' => $e->getMessage(),
                'dl_number' => $dlNumber,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'verified' => false,
                'message' => 'DL verification failed: ' . $e->getMessage(),
                'error_code' => 'DL_API_EXCEPTION'
            ];
        }
    }

    /**
     * ============================================
     * ✅ VERIFY DL (OLD METHOD - Keep for backward compatibility)
     * ============================================
     */
    public function verifyDl($dlNumber, $dob, $name = null)
    {
        try {
            Log::info('🚗 DL Verification Started (Old Method)', [
                'dl_number' => $dlNumber,
                'dob' => $dob,
                'name' => $name
            ]);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/driving-license/driving-license', [
                'id_number' => strtoupper($dlNumber),
                'dob' => $dob
            ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('📡 DL API Response', [
                'status_code' => $statusCode,
                'success' => $responseData['success'] ?? false
            ]);

            if ($statusCode !== 200 || !($responseData['success'] ?? false)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => $responseData['message'] ?? 'DL verification failed',
                    'error' => $responseData
                ];
            }

            $data = $responseData['data'] ?? [];
            $dlOwnerName = $data['name'] ?? null;
            $dlDob = $data['dob'] ?? null;

            if (empty($dlOwnerName)) {
                return [
                    'success' => false,
                    'verified' => false,
                    'message' => 'Invalid DL number. No license holder details found.'
                ];
            }

            if (!empty($dlDob) && $dob !== $dlDob) {
                return [
                    'success' => false,
                    'verified' => false,
                    'dob_match' => false,
                    'message' => 'Date of birth mismatch.',
                    'details' => [
                        'provided_dob' => $dob,
                        'dl_dob' => $dlDob
                    ]
                ];
            }

            $nameMatch = true;
            if ($name) {
                $nameMatch = $this->matchNames($name, $dlOwnerName);

                if (!$nameMatch) {
                    return [
                        'success' => false,
                        'verified' => false,
                        'name_match' => false,
                        'message' => 'Name mismatch.',
                        'details' => [
                            'provided_name' => $name,
                            'dl_owner_name' => $dlOwnerName
                        ]
                    ];
                }
            }

            $verifiedData = [
                'dl_number' => $data['license_number'] ?? $dlNumber,
                'name' => $dlOwnerName,
                'father_or_husband_name' => $data['father_or_husband_name'] ?? null,
                'dob' => $dlDob,
                'gender' => $data['gender'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'state' => $data['state'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'doi' => $data['doi'] ?? null,
                'doe' => $data['doe'] ?? null,
                'vehicle_classes' => $data['vehicle_classes'] ?? [],
                'verified_at' => now()->toDateTimeString(),
                'verification_method' => 'surepass_api',
                'government_verified' => true
            ];

            return [
                'success' => true,
                'verified' => true,
                'dob_match' => true,
                'name_match' => $nameMatch,
                'message' => 'DL verified successfully',
                'data' => $verifiedData
            ];

        } catch (\Exception $e) {
            Log::error('💥 DL Exception (Old Method)', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'verified' => false,
                'message' => 'DL verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initialize DigiLocker for Aadhaar
     */
    public function initializeDigilocker($redirectUrl = 'https://google.com')
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/digilocker/initialize', [
                'data' => [
                    'signup_flow' => true,
                    'redirect_url' => $redirectUrl
                ]
            ]);

            $responseData = $response->json();

            if ($response->status() === 200 && ($responseData['success'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'DigiLocker initialized successfully',
                    'data' => $responseData['data'] ?? []
                ];
            }

            return ['success' => false, 'message' => 'DigiLocker initialization failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'DigiLocker failed: ' . $e->getMessage()];
        }
    }

    /**
     * Download Aadhaar from DigiLocker
     */
    public function downloadAadhaarDigilocker($clientId, $providedName = null)
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $this->surepassToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/digilocker/download-aadhaar/' . $clientId);

            $responseData = $response->json();

            if ($response->status() === 200 && ($responseData['success'] ?? false)) {
                $data = $responseData['data'] ?? [];
                $aadhaarData = $data['aadhaar_xml_data'] ?? [];

                return [
                    'success' => true,
                    'verified' => true,
                    'message' => 'Aadhaar verified via DigiLocker',
                    'data' => $aadhaarData
                ];
            }

            return ['success' => false, 'message' => 'Aadhaar download failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Download failed: ' . $e->getMessage()];
        }
    }

    /**
     * Helper: Match Names
     */
    private function matchNames($name1, $name2)
    {
        $clean1 = strtolower(trim(preg_replace('/\s+/', ' ', $name1)));
        $clean2 = strtolower(trim(preg_replace('/\s+/', ' ', $name2)));

        if ($clean1 === $clean2) return true;
        if (str_contains($clean1, $clean2) || str_contains($clean2, $clean1)) return true;

        similar_text($clean1, $clean2, $percent);
        return $percent >= 75;
    }
}
