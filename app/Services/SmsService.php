<?php

namespace App\Services;

class SmsService
{
    private $apiKey;
    private $senderId;
    private $templateId;

    public function __construct()
    {
        $this->apiKey = env('FAST2SMS_API_KEY', 'sxNdKMHfGiCpn62Bw3WkVDeF54oUlTuhIcJg7L9myqOZERtzbaJvp4d8qOFgsfWMRPhVxzTLEauBCG1m');
        $this->senderId = env('FAST2SMS_SENDER_ID', 'DTLLP');
        $this->templateId = env('FAST2SMS_TEMPLATE_ID', '197967');
    }

    public function sendOtp($mobile, $otp, $type = 'registration')
    {
        try {
            $postData = [
                'sender_id' => $this->senderId,
                'message' => $this->templateId,
                'variables_values' => $otp, // 4-digit OTP
                'route' => 'dlt',
                'numbers' => $mobile
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://www.fast2sms.com/dev/bulkV2',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    "authorization: {$this->apiKey}",
                    "Content-Type: application/json",
                    "cache-control: no-cache"
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $responseData = json_decode($response, true);

            if ($httpCode == 200 && isset($responseData['return']) && $responseData['return'] === true) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'request_id' => $responseData['request_id'] ?? null
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Failed to send OTP',
                'error' => $response
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage()
            ];
        }
    }
}
