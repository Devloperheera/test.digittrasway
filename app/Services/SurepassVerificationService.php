<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SurepassVerificationService
{
    private $token;
    private $baseUrl;
    private $demoMode;

    public function __construct()
    {
        $this->baseUrl = 'https://kyc-api.surepass.app/api/v1';
        $this->token = env('SUREPASS_TOKEN');
        $this->demoMode = env('SUREPASS_DEMO_MODE', false);

        if (empty($this->token) && !$this->demoMode) {
            throw new \Exception('SUREPASS_TOKEN not configured in .env file');
        }

        Log::info('✅ Service initialized', [
            'demo_mode' => $this->demoMode
        ]);
    }

    public function verifyRc($rcNumber)
    {
        // ✅ DEMO MODE - Always returns success with fake data
        if ($this->demoMode) {
            Log::info('🎭 DEMO MODE - RC Verification');

            sleep(1); // Simulate API delay

            return [
                'success' => true,
                'message' => 'RC verified successfully (DEMO DATA)',
                'data' => $this->getDemoRcData($rcNumber)
            ];
        }

        // ✅ REAL API CALL
        try {
            Log::info('🚗 Real RC API Call', ['rc' => $rcNumber]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/rc/rc-full', [
                    'id_number' => strtoupper(trim($rcNumber))
                ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('📡 API Response', [
                'status' => $statusCode,
                'success' => $responseData['success'] ?? false
            ]);

            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'message' => 'API Error: Status ' . $statusCode,
                    'data' => null
                ];
            }

            if (!($responseData['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'RC not found in Government database',
                    'data' => null
                ];
            }

            $data = $responseData['data'] ?? [];

            // Check if data has meaningful content
            if ($this->isDataEmpty($data)) {
                return [
                    'success' => false,
                    'message' => 'RC exists but no data available from Government',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'RC verified successfully',
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('💥 Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function verifyDl($dlNumber, $dob)
    {
        // ✅ DEMO MODE
        if ($this->demoMode) {
            Log::info('🎭 DEMO MODE - DL Verification');

            sleep(1);

            return [
                'success' => true,
                'message' => 'DL verified successfully (DEMO DATA)',
                'data' => $this->getDemoDlData($dlNumber, $dob)
            ];
        }

        // ✅ REAL API CALL (same as before)
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/driving-license/driving-license', [
                    'id_number' => strtoupper(trim($dlNumber)),
                    'dob' => $dob
                ]);

            $statusCode = $response->status();
            $responseData = $response->json();

            if ($statusCode !== 200 || !($responseData['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'DL not found in Government database',
                    'data' => null
                ];
            }

            return [
                'success' => true,
                'message' => 'DL verified successfully',
                'data' => $responseData['data'] ?? []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Check if RC data is empty/null
     */
    private function isDataEmpty($data)
    {
        $importantFields = ['owner_name', 'vehicle_category', 'maker_model'];

        foreach ($importantFields as $field) {
            if (!empty($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate demo RC data
     */
    private function getDemoRcData($rcNumber)
    {
        return [
            'client_id' => 'demo_' . uniqid(),
            'rc_number' => strtoupper($rcNumber),
            'owner_name' => 'DEMO VEHICLE OWNER',
            'father_name' => 'DEMO FATHER NAME',
            'present_address' => 'DEMO ADDRESS, SECTOR 15, NEW DELHI, DELHI - 110001',
            'permanent_address' => 'DEMO ADDRESS, SECTOR 15, NEW DELHI, DELHI - 110001',
            'mobile_number' => '9XXXXXXXXX',
            'vehicle_category' => 'LMV',
            'vehicle_category_description' => 'Light Motor Vehicle',
            'vehicle_chasi_number' => 'MA3EXXXX' . rand(10000000, 99999999),
            'vehicle_engine_number' => 'K12M' . rand(100000, 999999),
            'maker_description' => 'MARUTI SUZUKI',
            'maker_model' => 'MARUTI SUZUKI SWIFT VXI',
            'body_type' => 'HATCHBACK',
            'fuel_type' => 'PETROL',
            'color' => 'WHITE',
            'norms_type' => 'BS VI',
            'financer' => null,
            'financed' => false,
            'insurance_company' => 'ICICI LOMBARD GENERAL INSURANCE CO LTD',
            'insurance_policy_number' => 'DEMO' . rand(1000000000, 9999999999),
            'insurance_upto' => date('Y-m-d', strtotime('+1 year')),
            'manufacturing_date' => '2020-01-15',
            'registered_at' => 'DELHI SOUTH',
            'registration_date' => '2020-02-10',
            'fit_up_to' => date('Y-m-d', strtotime('+10 years')),
            'tax_paid_upto' => date('Y-m-d', strtotime('+1 year')),
            'cubic_capacity' => '1197',
            'seat_capacity' => '5',
            'rc_status' => 'ACTIVE',
            'masked_name' => false,
            'less_info' => false
        ];
    }

    /**
     * Generate demo DL data
     */
    private function getDemoDlData($dlNumber, $dob)
    {
        return [
            'client_id' => 'demo_' . uniqid(),
            'license_number' => strtoupper($dlNumber),
            'name' => 'DEMO LICENSE HOLDER',
            'father_or_husband_name' => 'DEMO FATHER NAME',
            'dob' => $dob,
            'gender' => 'M',
            'blood_group' => 'O+',
            'state' => 'DELHI',
            'permanent_address' => 'DEMO ADDRESS, NEW DELHI, DELHI - 110001',
            'temporary_address' => 'DEMO ADDRESS, NEW DELHI, DELHI - 110001',
            'doi' => date('Y-m-d', strtotime('-5 years')),
            'doe' => date('Y-m-d', strtotime('+15 years')),
            'transport_doi' => '1800-01-01',
            'transport_doe' => '1800-01-01',
            'vehicle_classes' => ['MCWG', 'LMV'],
            'ola_name' => 'DEMO RTO',
            'ola_code' => 'DL-01',
            'has_image' => false,
            'less_info' => false
        ];
    }
}
