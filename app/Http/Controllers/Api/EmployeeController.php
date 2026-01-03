<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Exports\EmployeeReferralsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::withCount('referredUsers'); // ✅ Load referral count

        // Search functionality
        if ($request->has('search') && $request->search != '') {
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
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Department filter
        if ($request->has('department') && $request->department != '') {
            $query->where('department', $request->department);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('date_of_joining', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('date_of_joining', '<=', $request->date_to);
        }

        // Per page selection
        $perPage = $request->get('per_page', 25);

        // Get unique departments for filter
        $departments = Employee::distinct()->pluck('department');

        $employees = $query->latest()->paginate($perPage)->appends($request->all());

        return view('Website.employees.index', compact('employees', 'departments'));
    }

    /**
     * ✅ NEW: Show employee referrals
     */
    public function referrals(Request $request, $id)
    {
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
    }

    /**
     * ✅ NEW: Export referrals to Excel
     */
    public function exportReferralsExcel(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $referrals = $this->getFilteredReferrals($request, $employee);

        return Excel::download(
            new EmployeeReferralsExport($referrals, $employee),
            'referrals_' . $employee->emp_id . '_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * ✅ NEW: Export referrals to CSV
     */
    public function exportReferralsCsv(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $referrals = $this->getFilteredReferrals($request, $employee);

        return Excel::download(
            new EmployeeReferralsExport($referrals, $employee),
            'referrals_' . $employee->emp_id . '_' . date('Y-m-d_H-i-s') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * ✅ NEW: Export referrals to PDF
     */
    public function exportReferralsPdf(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $referrals = $this->getFilteredReferrals($request, $employee);

        $pdf = Pdf::loadView('Website.employees.referrals-pdf', compact('employee', 'referrals'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download('referrals_' . $employee->emp_id . '_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * ✅ Helper: Get filtered referrals
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

    public function create()
    {
        return view('Website.employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|max:15',
            'designation' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'date_of_joining' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:active,inactive'
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('employees', 'public');
        }

        Employee::create($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully!');
    }

    public function show($id)
    {
        $employee = Employee::with('referredUsers')->findOrFail($id);
        return view('Website.employees.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        return view('Website.employees.edit', compact('employee'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $id,
            'phone' => 'required|string|max:15',
            'designation' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'date_of_joining' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:active,inactive'
        ]);

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }
            $validated['photo'] = $request->file('photo')->store('employees', 'public');
        }

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully!');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        if ($employee->photo) {
            Storage::disk('public')->delete($employee->photo);
        }

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully!');
    }

    public function toggleStatus($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->status = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->save();

        return redirect()->route('employees.index')
            ->with('success', 'Employee status updated successfully!');
    }
}
