<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\SurepassVerificationService; // ✅ CHANGED: New service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

class DocumentVerificationController extends Controller
{
    protected $documentService;

    public function __construct(SurepassVerificationService $documentService) // ✅ CHANGED: Using new service
    {
        $this->documentService = $documentService;
    }

    /**
     * Show search form
     */
    public function index()
    {
        return view('Website.DocumentVerification.index');
    }

    /**
     * Search and verify document
     */
    public function search(Request $request)
    {
        // ✅ Enhanced validation with custom messages
        $validated = $request->validate([
            'document_type' => 'required|in:rc,dl',
            'id_number' => 'required|string|min:5|max:20',
            'dob' => 'required_if:document_type,dl|date|before:today'
        ], [
            'document_type.required' => 'Please select document type',
            'document_type.in' => 'Invalid document type selected',
            'id_number.required' => 'Please enter document number',
            'id_number.min' => 'Document number must be at least 5 characters',
            'id_number.max' => 'Document number cannot exceed 20 characters',
            'dob.required_if' => 'Date of birth is required for DL verification',
            'dob.date' => 'Please enter a valid date',
            'dob.before' => 'Date of birth must be before today'
        ]);

        $type = $request->document_type;
        $id = strtoupper(trim($request->id_number));
        $dob = $request->dob;

        // ✅ Log verification attempt
        Log::info('📋 Document Verification Initiated', [
            'type' => $type,
            'id_number' => $id,
            'dob' => $dob ?? 'N/A',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            if ($type === 'rc') {
                // ✅ RC Verification
                $result = $this->documentService->verifyRc($id);

                // ✅ Check for failure
                if (!$result['success']) {
                    Log::warning('❌ RC Verification Failed', [
                        'rc_number' => $id,
                        'message' => $result['message'] ?? 'Unknown error'
                    ]);

                    return back()
                        ->withInput()
                        ->with('error', $result['message'] ?? 'RC verification failed. Please check the RC number and try again.');
                }

                // ✅ Check for empty data
                if (empty($result['data'])) {
                    Log::warning('⚠️ RC Data Empty', ['rc_number' => $id]);

                    return back()
                        ->withInput()
                        ->with('error', 'No data found for this RC number. Please verify the number.');
                }

                Log::info('✅ RC Verification Successful', [
                    'rc_number' => $id,
                    'owner' => $result['data']['owner_name'] ?? 'N/A'
                ]);

                // ✅ Return result view
                return view('Website.DocumentVerification.rc_result', [
                    'data' => $result['data'],
                    'rc_number' => $id,
                    'verified_at' => now()->format('d M Y, h:i A')
                ]);

            } else {
                // ✅ DL Verification
                $result = $this->documentService->verifyDl($id, $dob);

                // ✅ Check for failure
                if (!$result['success']) {
                    Log::warning('❌ DL Verification Failed', [
                        'dl_number' => $id,
                        'dob' => $dob,
                        'message' => $result['message'] ?? 'Unknown error'
                    ]);

                    return back()
                        ->withInput()
                        ->with('error', $result['message'] ?? 'DL verification failed. Please check the DL number and date of birth.');
                }

                // ✅ Check for empty data
                if (empty($result['data'])) {
                    Log::warning('⚠️ DL Data Empty', [
                        'dl_number' => $id,
                        'dob' => $dob
                    ]);

                    return back()
                        ->withInput()
                        ->with('error', 'No data found for this DL number. Please verify the number and date of birth.');
                }

                Log::info('✅ DL Verification Successful', [
                    'dl_number' => $id,
                    'owner' => $result['data']['name'] ?? 'N/A'
                ]);

                // ✅ Return result view
                return view('Website.DocumentVerification.dl_result', [
                    'data' => $result['data'],
                    'dl_number' => $id,
                    'dob' => $dob,
                    'verified_at' => now()->format('d M Y, h:i A')
                ]);
            }

        } catch (\Exception $e) {
            // ✅ Enhanced error logging
            Log::error('💥 Document Verification Exception', [
                'type' => $type,
                'id_number' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Verification failed due to a technical issue. Please try again later.');
        }
    }

    /**
     * Export RC to PDF
     */
    public function exportRcPdf(Request $request)
    {
        try {
            $rcData = json_decode($request->input('rc_data'), true);

            if (!$rcData) {
                Log::warning('⚠️ RC Export Failed - No Data', [
                    'ip' => $request->ip()
                ]);
                return back()->with('error', 'No RC data available for export');
            }

            Log::info('📄 RC PDF Export Started', [
                'rc_number' => $rcData['rc_number'] ?? 'unknown'
            ]);

            // ✅ Check if view exists
            if (!view()->exists('pdfs.standalone_rc')) {
                throw new \Exception('PDF template not found');
            }

            $html = view('pdfs.standalone_rc', [
                'data' => $rcData,
                'generated_at' => now()->format('d M Y, h:i A')
            ])->render();

            $mpdf = new Mpdf([
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            $mpdf->WriteHTML($html);

            $filename = 'RC_' . ($rcData['rc_number'] ?? 'unknown') . '_' . date('Ymd') . '.pdf';

            Log::info('✅ RC PDF Generated', ['filename' => $filename]);

            return $mpdf->Output($filename, 'D');

        } catch (\Exception $e) {
            Log::error('💥 RC PDF Export Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export DL to PDF
     */
    public function exportDlPdf(Request $request)
    {
        try {
            $dlData = json_decode($request->input('dl_data'), true);

            if (!$dlData) {
                Log::warning('⚠️ DL Export Failed - No Data', [
                    'ip' => $request->ip()
                ]);
                return back()->with('error', 'No DL data available for export');
            }

            Log::info('📄 DL PDF Export Started', [
                'dl_number' => $dlData['dl_number'] ?? 'unknown'
            ]);

            // ✅ Check if view exists
            if (!view()->exists('pdfs.standalone_dl')) {
                throw new \Exception('PDF template not found');
            }

            $html = view('pdfs.standalone_dl', [
                'data' => $dlData,
                'generated_at' => now()->format('d M Y, h:i A')
            ])->render();

            $mpdf = new Mpdf([
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            $mpdf->WriteHTML($html);

            $filename = 'DL_' . ($dlData['dl_number'] ?? 'unknown') . '_' . date('Ymd') . '.pdf';

            Log::info('✅ DL PDF Generated', ['filename' => $filename]);

            return $mpdf->Output($filename, 'D');

        } catch (\Exception $e) {
            Log::error('💥 DL PDF Export Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
}
