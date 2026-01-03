<?php

namespace App\Http\Controllers\Website;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\UsersExport;
use App\Exports\UserDetailExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Display all users with filters and pagination
     */
    public function index(Request $request)
    {
        $query = User::with('referredByEmployee'); // ✅ Load employee relationship

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('contact_number', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('aadhar_number', 'LIKE', "%{$search}%")
                    ->orWhere('pan_number', 'LIKE', "%{$search}%")
                    ->orWhere('referral_emp_id', 'LIKE', "%{$search}%"); // ✅ Search by emp ID
            });
        }

        // Date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Status filters
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        if ($request->filled('is_completed')) {
            $query->where('is_completed', $request->is_completed);
        }

        // ✅ NEW: Employee filter
        if ($request->filled('employee_id')) {
            $query->where('referred_by_employee_id', $request->employee_id);
        }

        // Per page selection
        $perPage = $request->get('per_page', 25);

        // Order by latest
        $query->orderBy('created_at', 'desc');

        // Paginate with query string
        $users = $query->paginate($perPage)->withQueryString();

        // ✅ Get all active employees for dropdown
        $employees = Employee::where('status', 'active')
                            ->orderBy('name')
                            ->get();

        return view('Website.User.index', compact('users', 'employees'));
    }

    /**
     * Show single user full details with subscription
     */
    public function show($id)
    {
        $user = User::with(['activeSubscription', 'subscriptions', 'referredByEmployee'])
                    ->findOrFail($id);

        return view('Website.User.show', compact('user'));
    }

    /**
     * Export filtered users to Excel
     */
    public function exportExcel(Request $request)
    {
        $users = $this->getFilteredUsers($request);

        return Excel::download(
            new UsersExport($users),
            'users_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export filtered users to CSV
     */
    public function exportCsv(Request $request)
    {
        $users = $this->getFilteredUsers($request);

        return Excel::download(
            new UsersExport($users),
            'users_' . date('Y-m-d_H-i-s') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Export filtered users to PDF
     */
    public function exportPdf(Request $request)
    {
        $users = $this->getFilteredUsers($request);

        $pdf = Pdf::loadView('Website.User.pdf', compact('users'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('users_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Get filtered users for export
     */
    private function getFilteredUsers(Request $request)
    {
        $query = User::with('referredByEmployee'); // ✅ Load employee relationship

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('contact_number', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('aadhar_number', 'LIKE', "%{$search}%")
                    ->orWhere('pan_number', 'LIKE', "%{$search}%")
                    ->orWhere('referral_emp_id', 'LIKE', "%{$search}%"); // ✅ Added
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        if ($request->filled('is_completed')) {
            $query->where('is_completed', $request->is_completed);
        }

        // ✅ NEW: Employee filter
        if ($request->filled('employee_id')) {
            $query->where('referred_by_employee_id', $request->employee_id);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Export single user detail to Excel
     */
    public function exportUserExcel($id)
    {
        $user = User::with('referredByEmployee')->findOrFail($id);

        return Excel::download(
            new UserDetailExport($user),
            'user_detail_' . $user->id . '_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export single user detail to CSV
     */
    public function exportUserCsv($id)
    {
        $user = User::with('referredByEmployee')->findOrFail($id);

        return Excel::download(
            new UserDetailExport($user),
            'user_detail_' . $user->id . '_' . date('Y-m-d_H-i-s') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Export single user detail to PDF
     */
    public function exportUserPdf($id)
    {
        $user = User::with('referredByEmployee')->findOrFail($id);

        $pdf = Pdf::loadView('Website.User.user-pdf', compact('user'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('user_detail_' . $user->id . '_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Print single user detail
     */
    public function printUser($id)
    {
        $user = User::with('referredByEmployee')->findOrFail($id);

        return view('Website.User.print', compact('user'));
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->is_completed = !$user->is_completed;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'status' => $user->is_completed
        ]);
    }
}
