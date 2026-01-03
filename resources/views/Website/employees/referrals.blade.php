@extends('Website.Layout.master')

@section('content')
<div class="content-area">
    <div class="container-fluid">

        {{-- Header with Employee Info --}}
        <div class="card mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 style="margin: 0; color: white;">
                            <i class="fas fa-user-tie me-2"></i>
                            {{ $employee->name }} ({{ $employee->emp_id }})
                        </h3>
                        <p style="margin: 5px 0 0; opacity: 0.9;">
                            {{ $employee->designation }} - {{ $employee->department }}
                        </p>
                    </div>
                    <div class="text-end">
                        <h2 style="margin: 0; font-weight: 700;">{{ $stats['total_installs'] }}</h2>
                        <p style="margin: 0; opacity: 0.9;">Total App Installs</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #667eea;">
                    <div class="card-body">
                        <h3 style="color: #667eea; margin: 0;">{{ $stats['total_installs'] }}</h3>
                        <p class="text-muted mb-0">Total Installs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #28a745;">
                    <div class="card-body">
                        <h3 style="color: #28a745; margin: 0;">{{ $stats['today_installs'] }}</h3>
                        <p class="text-muted mb-0">Today's Installs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #ffc107;">
                    <div class="card-body">
                        <h3 style="color: #ffc107; margin: 0;">{{ $stats['month_installs'] }}</h3>
                        <p class="text-muted mb-0">This Month</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body">
                        <h3 style="color: #17a2b8; margin: 0;">{{ $stats['verified_users'] }}</h3>
                        <p class="text-muted mb-0">Verified Users</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters & Export --}}
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('employees.referrals', $employee->id) }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Name, Phone, Email..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="is_verified" class="form-control">
                                <option value="">All</option>
                                <option value="1" {{ request('is_verified') == '1' ? 'selected' : '' }}>Verified</option>
                                <option value="0" {{ request('is_verified') == '0' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">From Date</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">To Date</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                                <a href="{{ route('employees.referrals', $employee->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-redo me-1"></i>Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Export Buttons & Per Page --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <label class="me-2">Show:</label>
                        <select name="per_page" class="form-select d-inline-block" style="width: auto;" onchange="changePerPage(this.value)">
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div>
                        <a href="{{ route('employees.referrals.export.excel', ['id' => $employee->id, request()->all()]) }}"
                           class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Export Excel
                        </a>
                        <a href="{{ route('employees.referrals.export.csv', ['id' => $employee->id, request()->all()]) }}"
                           class="btn btn-info btn-sm">
                            <i class="fas fa-file-csv me-1"></i>Export CSV
                        </a>
                        <a href="{{ route('employees.referrals.export.pdf', ['id' => $employee->id, request()->all()]) }}"
                           class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Users Table --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Install Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($referrals as $user)
                            <tr>
                                <td>
                                    @if($user->aadhar_front)
                                    <img src="{{ asset('storage/' . $user->aadhar_front) }}"
                                         alt="User"
                                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                                    @else
                                    <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user text-muted"></i>
                                    </div>
                                    @endif
                                </td>
                                <td>{{ $user->name ?? 'N/A' }}</td>
                                <td>{{ $user->contact_number }}</td>
                                <td>{{ $user->email ?? 'N/A' }}</td>
                                <td>{{ $user->app_installed_at ? $user->app_installed_at->format('d M, Y h:i A') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->is_verified ? 'success' : 'warning' }}">
                                        {{ $user->is_verified ? 'Verified' : 'Pending' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $referrals->firstItem() ?? 0 }} to {{ $referrals->lastItem() ?? 0 }}
                        of {{ $referrals->total() }} entries
                    </div>
                    <div>
                        {{ $referrals->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Back Button --}}
        <div class="mt-3">
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Employees
            </a>
        </div>

    </div>
</div>
@endsection

@section('custom_js')
<script>
function changePerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}
</script>
@endsection
