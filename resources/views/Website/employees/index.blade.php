@extends('Website.Layout.master')

@section('custom_css')
<style>
    .employee-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    .badge {
        padding: 5px 10px;
        font-size: 12px;
    }

    .alert {
        border-radius: 8px;
    }

    .form-select, .form-control {
        border-radius: 6px;
    }
</style>
@endsection

@section('content')
<div class="content-area">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #265b6b; font-weight: 700;">Employee Management</h2>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New Employee
            </a>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Search & Filter Section --}}
        <div class="card employee-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('employees.index') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-search me-1"></i>Search</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Search by ID, Name, Email, Phone..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-toggle-on me-1"></i>Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-building me-1"></i>Department</label>
                            <select name="department" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-calendar me-1"></i>From Date</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-calendar me-1"></i>To Date</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" max="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                            <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data Table Section --}}
        <div class="card employee-card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <label class="me-2">Show:</label>
                        <select name="per_page" class="form-select d-inline-block" style="width: auto;" onchange="changePerPage(this.value)">
                            <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            <option value="250" {{ request('per_page') == 250 ? 'selected' : '' }}>250</option>
                            <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                        </select>
                        <span class="ms-2 text-muted">entries</span>
                    </div>
                    <div>
                        <strong>Total: {{ $employees->total() }}</strong> employees
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th style="width: 10%;">Emp ID</th>
                                <th style="width: 15%;">Name</th>
                                <th style="width: 15%;">Email</th>
                                <th style="width: 12%;">Phone</th>
                                <th style="width: 15%;">Designation</th>
                                <th style="width: 13%;">Department</th>
                                <th style="width: 10%;" class="text-center">Status</th>
                                <th style="width: 10%;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $employee)
                            <tr>
                                <td><strong class="text-primary">{{ $employee->emp_id }}</strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($employee->photo)
                                        <img src="{{ asset('storage/' . $employee->photo) }}"
                                             alt="{{ $employee->name }}"
                                             class="rounded-circle me-2"
                                             style="width: 35px; height: 35px; object-fit: cover;">
                                        @else
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                                             style="width: 35px; height: 35px; font-size: 14px;">
                                            {{ strtoupper(substr($employee->name, 0, 1)) }}
                                        </div>
                                        @endif
                                        <span>{{ $employee->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $employee->email }}</td>
                                <td>{{ $employee->phone }}</td>
                                <td>{{ $employee->designation }}</td>
                                <td>{{ $employee->department }}</td>
                                <td class="text-center">
                                    <form action="{{ route('employees.toggle-status', $employee->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit"
                                                class="badge bg-{{ $employee->status === 'active' ? 'success' : 'danger' }} border-0"
                                                style="cursor: pointer;"
                                                onclick="return confirm('Are you sure you want to change status?')">
                                            {{ ucfirst($employee->status) }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('employees.show', $employee->id) }}"
                                           class="btn btn-sm btn-info"
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('employees.edit', $employee->id) }}"
                                           class="btn btn-sm btn-warning"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                title="Delete"
                                                onclick="deleteEmployee({{ $employee->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $employee->id }}"
                                          action="{{ route('employees.destroy', $employee->id) }}"
                                          method="POST"
                                          style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No employees found</p>
                                    @if(request()->hasAny(['search', 'status', 'department', 'date_from', 'date_to']))
                                    <a href="{{ route('employees.index') }}" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-redo me-1"></i>Clear Filters
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($employees->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Showing <strong>{{ $employees->firstItem() ?? 0 }}</strong> to
                        <strong>{{ $employees->lastItem() ?? 0 }}</strong> of
                        <strong>{{ $employees->total() }}</strong> entries
                    </div>
                    <div>
                        {{ $employees->links() }}
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection

@section('custom_js')
<script>
// Change per page
function changePerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

// Delete employee with confirmation
function deleteEmployee(id) {
    if (confirm('Are you sure you want to delete this employee?\n\nThis action cannot be undone!')) {
        document.getElementById('delete-form-' + id).submit();
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>
@endsection
