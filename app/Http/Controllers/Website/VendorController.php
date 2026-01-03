<?php

namespace App\Http\Controllers\Website;

use App\Models\Vendor;
use App\Models\Employee;
use App\Services\DocumentVerificationService;
use App\Services\PdfService;
use App\Exports\VendorsExport;
use App\Exports\RcDetailsExport;
use App\Exports\DlDetailsExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends Controller
{
    protected $documentService;
    protected $pdfService;

    public function __construct(DocumentVerificationService $documentService, PdfService $pdfService)
    {
        $this->documentService = $documentService;
        $this->pdfService = $pdfService;
    }

    /**
     * Display all vendors with filters
     */
    public function index(Request $request)
    {
        $query = Vendor::with(['vehicleCategory', 'vehicleModel', 'referredByEmployee']); // ✅ Added employee

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('contact_number', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('rc_number', 'LIKE', "%{$search}%")
                  ->orWhere('dl_number', 'LIKE', "%{$search}%")
                  ->orWhere('referral_emp_id', 'LIKE', "%{$search}%"); // ✅ Added
            });
        }

        // ✅ NEW: Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('referred_by_employee_id', $request->employee_id);
        }

        // Filter by verification status
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        // Filter by RC verification
        if ($request->filled('rc_verified')) {
            $query->where('rc_verified', $request->rc_verified);
        }

        // Filter by DL verification
        if ($request->filled('dl_verified')) {
            $query->where('dl_verified', $request->dl_verified);
        }

        // Filter by availability status
        if ($request->filled('availability_status')) {
            $query->where('availability_status', $request->availability_status);
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $vendors = $query->latest()->paginate(25)->withQueryString();

        // ✅ Get all active employees for dropdown
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        return view('Website.Vendor.index', compact('vendors', 'employees'));
    }

    /**
     * Show vendor details
     */
    public function show($id)
    {
        $vendor = Vendor::with(['vehicleCategory', 'vehicleModel', 'vehicles', 'referredByEmployee']) // ✅ Added
                       ->findOrFail($id);

        return view('Website.Vendor.show', compact('vendor'));
    }

    /**
     * View RC Details
     */
    public function viewRcDetails($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (!$vendor->rc_verified || empty($vendor->rc_verified_data)) {
            return redirect()->back()->with('error', 'RC not verified yet');
        }

        $rcData = $vendor->rc_verified_data;

        return view('Website.Vendor.rc_details', compact('vendor', 'rcData'));
    }

    /**
     * View DL Details
     */
    public function viewDlDetails($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (!$vendor->dl_verified || empty($vendor->dl_verified_data)) {
            return redirect()->back()->with('error', 'DL not verified yet');
        }

        $dlData = $vendor->dl_verified_data;

        return view('Website.Vendor.dl_details', compact('vendor', 'dlData'));
    }

    /**
     * Toggle vendor status
     */
    public function toggleStatus($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->is_verified = !$vendor->is_verified;
            $vendor->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'is_verified' => $vendor->is_verified
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Export Vendors to Excel
     */
    public function exportExcel(Request $request)
    {
        $vendors = $this->getFilteredVendors($request); // ✅ Changed to get collection

        return Excel::download(new VendorsExport($vendors), 'vendors_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export Vendors to CSV
     */
    public function exportCsv(Request $request)
    {
        $vendors = $this->getFilteredVendors($request); // ✅ Changed

        return Excel::download(new VendorsExport($vendors), 'vendors_' . date('Y-m-d') . '.csv');
    }

    /**
     * Export Vendors to PDF
     */
    public function exportPdf(Request $request)
    {
        $vendors = $this->getFilteredVendors($request); // ✅ Changed

        return $this->pdfService->generateVendorsPdf($vendors);
    }

    /**
     * Export RC Details to Excel
     */
    public function exportRcExcel($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (empty($vendor->rc_verified_data)) {
            return redirect()->back()->with('error', 'No RC data available');
        }

        return Excel::download(
            new RcDetailsExport($vendor->rc_verified_data),
            'rc_details_' . $vendor->id . '.xlsx'
        );
    }

    /**
     * Export RC Details to CSV
     */
    public function exportRcCsv($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (empty($vendor->rc_verified_data)) {
            return redirect()->back()->with('error', 'No RC data available');
        }

        return Excel::download(
            new RcDetailsExport($vendor->rc_verified_data),
            'rc_details_' . $vendor->id . '.csv'
        );
    }

    /**
     * Export RC Details to PDF
     */
    public function exportRcPdf($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (empty($vendor->rc_verified_data)) {
            return redirect()->back()->with('error', 'No RC data available');
        }

        return $this->pdfService->generateRcDetailsPdf($vendor, $vendor->rc_verified_data);
    }

    /**
     * Export DL Details to Excel
     */
    public function exportDlExcel($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (empty($vendor->dl_verified_data)) {
            return redirect()->back()->with('error', 'No DL data available');
        }

        return Excel::download(
            new DlDetailsExport($vendor->dl_verified_data),
            'dl_details_' . $vendor->id . '.xlsx'
        );
    }

    /**
     * Export DL Details to CSV
     */
    public function exportDlCsv($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (empty($vendor->dl_verified_data)) {
            return redirect()->back()->with('error', 'No DL data available');
        }

        return Excel::download(
            new DlDetailsExport($vendor->dl_verified_data),
            'dl_details_' . $vendor->id . '.csv'
        );
    }

    /**
     * Export DL Details to PDF
     */
    public function exportDlPdf($id)
    {
        $vendor = Vendor::findOrFail($id);

        if (empty($vendor->dl_verified_data)) {
            return redirect()->back()->with('error', 'No DL data available');
        }

        return $this->pdfService->generateDlDetailsPdf($vendor, $vendor->dl_verified_data);
    }

    /**
     * ✅ UPDATED: Get filtered vendors (returns collection with employee relationship)
     */
    private function getFilteredVendors(Request $request)
    {
        $query = Vendor::with(['vehicleCategory', 'vehicleModel', 'referredByEmployee']); // ✅ Added employee

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('contact_number', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('referral_emp_id', 'LIKE', "%{$search}%"); // ✅ Added
            });
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        // ✅ NEW: Employee filter
        if ($request->filled('employee_id')) {
            $query->where('referred_by_employee_id', $request->employee_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return $query->latest()->get(); // ✅ Return collection instead of query
    }
}
