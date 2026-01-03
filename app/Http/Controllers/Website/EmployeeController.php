<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Exports\EmployeeReferralsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeController extends Controller
{
    /**
     * Display listing of employees
     */
    public function index(Request $request)
    {
        try {
            $query = Employee::withCount('referredUsers');

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('emp_id', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('designation', 'like', "%{$search}%")
                      ->orWhere('department', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Department filter
            if ($request->filled('department')) {
                $query->where('department', $request->department);
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('date_of_joining', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date_of_joining', '<=', $request->date_to);
            }

            // Per page selection
            $perPage = $request->get('per_page', 25);

            // Get unique departments for filter
            $departments = Employee::distinct()
                ->pluck('department')
                ->filter()
                ->sort()
                ->values();

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $employees = $query->paginate($perPage)->appends($request->all());

            return view('Website.employees.index', compact('employees', 'departments'));

        } catch (\Exception $e) {
            Log::error('Employee Index Error: ' . $e->getMessage());

            return back()->with('error', 'Error loading employees: ' . $e->getMessage());
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('Website.employees.create');
    }

    /**
     * Store new employee
     */
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|max:20',
            'designation' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'date_of_joining' => 'required|date|before_or_equal:today',
            'salary' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',

            // Document validations
            'aadhar_front' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'aadhar_back' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'pan_card' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'driving_license' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'address_proof' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',

            'aadhar_number' => 'nullable|string|max:12',
            'pan_number' => 'nullable|string|max:10',
            'dl_number' => 'nullable|string|max:20',
        ], [
            'email.unique' => 'This email is already registered.',
            'date_of_joining.before_or_equal' => 'Joining date cannot be in the future.',
        ]);

        DB::beginTransaction();

        try {
            // Handle Photo Upload
            if ($request->hasFile('photo')) {
                $validated['photo'] = $request->file('photo')
                    ->store('employees/photos', 'public');
            }

            // Handle Document Uploads
            if ($request->hasFile('aadhar_front')) {
                $validated['aadhar_front'] = $request->file('aadhar_front')
                    ->store('employees/documents/aadhar', 'public');
            }

            if ($request->hasFile('aadhar_back')) {
                $validated['aadhar_back'] = $request->file('aadhar_back')
                    ->store('employees/documents/aadhar', 'public');
            }

            if ($request->hasFile('pan_card')) {
                $validated['pan_card'] = $request->file('pan_card')
                    ->store('employees/documents/pan', 'public');
            }

            if ($request->hasFile('driving_license')) {
                $validated['driving_license'] = $request->file('driving_license')
                    ->store('employees/documents/dl', 'public');
            }

            if ($request->hasFile('address_proof')) {
                $validated['address_proof'] = $request->file('address_proof')
                    ->store('employees/documents/address', 'public');
            }

            // Create Employee
            $employee = Employee::create($validated);

            DB::commit();

            Log::info('Employee Created: ' . $employee->emp_id);

            return redirect()
                ->route('employees.index')
                ->with('success', "Employee created successfully! Employee ID: {$employee->emp_id}");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Creation Failed: ' . $e->getMessage());

            // Delete uploaded files if employee creation failed
            $filesToDelete = ['photo', 'aadhar_front', 'aadhar_back', 'pan_card', 'driving_license', 'address_proof'];
            foreach ($filesToDelete as $field) {
                if (isset($validated[$field]) && $validated[$field]) {
                    Storage::disk('public')->delete($validated[$field]);
                }
            }

            return back()
                ->withInput()
                ->with('error', 'Error creating employee: ' . $e->getMessage());
        }
    }

    /**
     * Show employee details
     */
    public function show($id)
    {
        try {
            $employee = Employee::with('referredUsers')->findOrFail($id);

            // Get install statistics
            $stats = [
                'total_installs' => $employee->referredUsers()->count(),
                'today_installs' => $employee->referredUsers()
                    ->whereDate('app_installed_at', today())
                    ->count(),
                'week_installs' => $employee->referredUsers()
                    ->whereBetween('app_installed_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])
                    ->count(),
                'month_installs' => $employee->referredUsers()
                    ->whereMonth('app_installed_at', now()->month)
                    ->whereYear('app_installed_at', now()->year)
                    ->count(),
                'verified_users' => $employee->referredUsers()
                    ->where('is_verified', true)
                    ->count(),
            ];

            // Recent referrals
            $recentReferrals = $employee->referredUsers()
                ->latest('app_installed_at')
                ->limit(10)
                ->get();

            return view('Website.employees.show', compact('employee', 'stats', 'recentReferrals'));

        } catch (\Exception $e) {
            Log::error('Employee Show Error: ' . $e->getMessage());

            return redirect()
                ->route('employees.index')
                ->with('error', 'Employee not found.');
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        try {
            $employee = Employee::findOrFail($id);

            return view('Website.employees.edit', compact('employee'));

        } catch (\Exception $e) {
            Log::error('Employee Edit Error: ' . $e->getMessage());

            return redirect()
                ->route('employees.index')
                ->with('error', 'Employee not found.');
        }
    }

    /**
     * Update employee
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        // Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $id,
            'phone' => 'required|string|max:20',
            'designation' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'date_of_joining' => 'required|date|before_or_equal:today',
            'salary' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'address' => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',

            // Document validations
            'aadhar_front' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'aadhar_back' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'pan_card' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'driving_license' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'address_proof' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',

            'aadhar_number' => 'nullable|string|max:12',
            'pan_number' => 'nullable|string|max:10',
            'dl_number' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();

        try {
            // Handle Photo Upload
            if ($request->hasFile('photo')) {
                if ($employee->photo) {
                    Storage::disk('public')->delete($employee->photo);
                }
                $validated['photo'] = $request->file('photo')
                    ->store('employees/photos', 'public');
            }

            // Handle Document Uploads
            $documents = [
                'aadhar_front' => 'employees/documents/aadhar',
                'aadhar_back' => 'employees/documents/aadhar',
                'pan_card' => 'employees/documents/pan',
                'driving_license' => 'employees/documents/dl',
                'address_proof' => 'employees/documents/address',
            ];

            foreach ($documents as $field => $path) {
                if ($request->hasFile($field)) {
                    // Delete old file
                    if ($employee->$field) {
                        Storage::disk('public')->delete($employee->$field);
                    }
                    $validated[$field] = $request->file($field)->store($path, 'public');
                }
            }

            // Update Employee
            $employee->update($validated);

            DB::commit();

            Log::info('Employee Updated: ' . $employee->emp_id);

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Employee updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Update Failed: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Error updating employee: ' . $e->getMessage());
        }
    }

    /**
     * Delete employee
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $employee = Employee::findOrFail($id);

            // Store details for logging
            $empId = $employee->emp_id;

            // Delete all uploaded files
            $files = [
                $employee->photo,
                $employee->aadhar_front,
                $employee->aadhar_back,
                $employee->pan_card,
                $employee->driving_license,
                $employee->address_proof,
            ];

            foreach ($files as $file) {
                if ($file && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }

            // Delete employee
            $employee->delete();

            DB::commit();

            Log::warning('Employee Deleted: ' . $empId);

            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Employee Delete Failed: ' . $e->getMessage());

            return back()->with('error', 'Error deleting employee: ' . $e->getMessage());
        }
    }

    /**
     * Toggle employee status
     */
    public function toggleStatus($id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
            $employee->update(['status' => $newStatus]);

            Log::info('Employee Status Changed: ' . $employee->emp_id . ' to ' . $newStatus);

            return back()->with('success', 'Employee status updated successfully!');

        } catch (\Exception $e) {
            Log::error('Toggle Status Failed: ' . $e->getMessage());

            return back()->with('error', 'Error updating status: ' . $e->getMessage());
        }
    }

    /**
     * Show employee referrals
     */
    public function referrals(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            $query = User::where('referred_by_employee_id', $employee->id);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('contact_number', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Status filter
            if ($request->filled('is_verified')) {
                $query->where('is_verified', $request->is_verified);
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $query->whereDate('app_installed_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('app_installed_at', '<=', $request->date_to);
            }

            // Per page
            $perPage = $request->get('per_page', 25);

            $referrals = $query->orderBy('app_installed_at', 'desc')
                              ->paginate($perPage)
                              ->withQueryString();

            // Stats
            $stats = [
                'total_installs' => $employee->referredUsers()->count(),
                'today_installs' => $employee->referredUsers()
                    ->whereDate('app_installed_at', today())
                    ->count(),
                'month_installs' => $employee->referredUsers()
                    ->whereMonth('app_installed_at', now()->month)
                    ->whereYear('app_installed_at', now()->year)
                    ->count(),
                'verified_users' => $employee->referredUsers()
                    ->where('is_verified', true)
                    ->count(),
            ];

            return view('Website.employees.referrals', compact('employee', 'referrals', 'stats'));

        } catch (\Exception $e) {
            Log::error('Employee Referrals Error: ' . $e->getMessage());

            return redirect()
                ->route('employees.index')
                ->with('error', 'Error loading referrals.');
        }
    }

    /**
     * Export referrals to Excel
     */
    public function exportReferralsExcel(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $referrals = $this->getFilteredReferrals($request, $employee);

            return Excel::download(
                new EmployeeReferralsExport($referrals, $employee),
                'referrals_' . $employee->emp_id . '_' . date('Y-m-d_H-i-s') . '.xlsx'
            );

        } catch (\Exception $e) {
            Log::error('Export Excel Failed: ' . $e->getMessage());

            return back()->with('error', 'Error exporting to Excel.');
        }
    }

    /**
     * Export referrals to CSV
     */
    public function exportReferralsCsv(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $referrals = $this->getFilteredReferrals($request, $employee);

            return Excel::download(
                new EmployeeReferralsExport($referrals, $employee),
                'referrals_' . $employee->emp_id . '_' . date('Y-m-d_H-i-s') . '.csv',
                \Maatwebsite\Excel\Excel::CSV
            );

        } catch (\Exception $e) {
            Log::error('Export CSV Failed: ' . $e->getMessage());

            return back()->with('error', 'Error exporting to CSV.');
        }
    }

    /**
     * Export referrals to PDF
     */
    public function exportReferralsPdf(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $referrals = $this->getFilteredReferrals($request, $employee);

            $pdf = Pdf::loadView('Website.employees.referrals-pdf', compact('employee', 'referrals'))
                      ->setPaper('a4', 'landscape');

            return $pdf->download('referrals_' . $employee->emp_id . '_' . date('Y-m-d_H-i-s') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Export PDF Failed: ' . $e->getMessage());

            return back()->with('error', 'Error exporting to PDF.');
        }
    }

    /**
     * View employee document
     */
    public function viewDocument($id, $type)
    {
        try {
            $employee = Employee::findOrFail($id);

            $validTypes = ['photo', 'aadhar_front', 'aadhar_back', 'pan_card', 'driving_license', 'address_proof'];

            if (!in_array($type, $validTypes)) {
                abort(404, 'Invalid document type');
            }

            $filePath = $employee->$type;

            if (!$filePath || !Storage::disk('public')->exists($filePath)) {
                abort(404, 'Document not found');
            }

            $fullPath = storage_path('app/public/' . $filePath);

            return response()->file($fullPath);

        } catch (\Exception $e) {
            Log::error('View Document Failed: ' . $e->getMessage());

            abort(404, 'Document not found');
        }
    }

    /**
     * Download employee document (FIXED)
     */
    public function downloadDocument($id, $type)
    {
        try {
            $employee = Employee::findOrFail($id);

            $validTypes = ['photo', 'aadhar_front', 'aadhar_back', 'pan_card', 'driving_license', 'address_proof'];

            if (!in_array($type, $validTypes)) {
                abort(404, 'Invalid document type');
            }

            $filePath = $employee->$type;

            if (!$filePath || !Storage::disk('public')->exists($filePath)) {
                abort(404, 'Document not found');
            }

            // Get file extension
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);

            // Create filename with employee ID
            $fileName = $employee->emp_id . '_' . $type . '.' . $extension;

            // Get full storage path
            $fullPath = storage_path('app/public/' . $filePath);

            // Return download response
            return response()->download($fullPath, $fileName);

        } catch (\Exception $e) {
            Log::error('Download Document Failed: ' . $e->getMessage());

            abort(404, 'Document not found');
        }
    }

    /**
     * Helper: Get filtered referrals
     */
    private function getFilteredReferrals(Request $request, Employee $employee)
    {
        $query = User::where('referred_by_employee_id', $employee->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('contact_number', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('app_installed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('app_installed_at', '<=', $request->date_to);
        }

        return $query->orderBy('app_installed_at', 'desc')->get();
    }
}
