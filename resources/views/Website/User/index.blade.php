@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .badge {
            padding: 5px 10px;
            font-size: 12px;
        }

        .status-toggle {
            cursor: pointer;
        }

        .document-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .document-thumb:hover {
            transform: scale(1.1);
        }

        .btn-group {
            gap: 5px;
        }

        .pagination-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .emp-link {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .emp-link:hover {
            text-decoration: underline;
            color: #0a58ca;
        }

        /* ✅ NEW: User ID Badge Style */
        .user-id-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4 mb-5">
        <div id="table-section" class="table-container">
            <!-- Header Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-users me-2"></i>
                        All Users Management
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <a href="{{ route('users.export.excel', request()->all()) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="{{ route('users.export.csv', request()->all()) }}" class="btn btn-info">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="{{ route('users.export.pdf', request()->all()) }}" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-card">
                <form method="GET" action="{{ route('users.index') }}" id="filterForm">
                    <div class="row g-3">
                        <!-- Search Bar -->
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-search"></i> Search</label>
                            <input type="text" name="search" class="form-control"
                                placeholder="User ID, Name, Contact, Email, Aadhaar, PAN..."
                                value="{{ request('search') }}">
                        </div>

                        <!-- Employee Filter -->
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-user-tie"></i> Employee</label>
                            <select name="employee_id" class="form-select">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->emp_id }} - {{ $emp->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-calendar"></i> Start Date</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ request('start_date') }}">
                        </div>

                        <!-- End Date -->
                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-calendar"></i> End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>

                        <!-- Buttons -->
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table Section -->
            <div class="table-container">
                <!-- Per Page & Pagination Info -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="pagination-info">
                            <label class="me-2">Show:</label>
                            <select class="form-select form-select-sm" style="width: auto;" id="perPageSelect">
                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                            </select>
                            <span class="text-muted ms-2">entries</span>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="text-muted mb-0">
                            Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of
                            {{ $users->total() }} users
                        </p>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Emp ID</th>
                                <th>Employee Name</th>
                                <th>Documents</th>
                                <th>Verified</th>
                                <th>Completed</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>

                                    <!-- ✅ USER ID COLUMN -->
                                    <td>
                                        <span class="user-id-badge">
                                            {{ $user->user_id ?? 'N/A' }}
                                        </span>
                                    </td>

                                    <td>{{ $user->name ?? 'N/A' }}</td>
                                    <td>{{ $user->contact_number }}</td>
                                    <td>{{ $user->email ?? 'N/A' }}</td>

                                    <!-- Employee ID (Clickable) -->
                                    <td>
                                        @if($user->referredByEmployee)
                                            <a href="{{ route('employees.show', $user->referredByEmployee->id) }}"
                                               class="emp-link"
                                               title="View Employee Details">
                                                {{ $user->referredByEmployee->emp_id }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <!-- Employee Name -->
                                    <td>
                                        @if($user->referredByEmployee)
                                            {{ $user->referredByEmployee->name }}
                                        @else
                                            <span class="text-muted">No Referral</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">
                                            @if ($user->aadhar_front)
                                                <img src="{{ asset('storage/' . $user->aadhar_front) }}"
                                                    class="document-thumb" alt="Aadhaar Front" title="Aadhaar Front"
                                                    onclick="openImageModal(this.src)">
                                            @endif
                                            @if ($user->aadhar_back)
                                                <img src="{{ asset('storage/' . $user->aadhar_back) }}"
                                                    class="document-thumb" alt="Aadhaar Back" title="Aadhaar Back"
                                                    onclick="openImageModal(this.src)">
                                            @endif
                                            @if ($user->pan_image)
                                                <img src="{{ asset('storage/' . $user->pan_image) }}"
                                                    class="document-thumb" alt="PAN" title="PAN Card"
                                                    onclick="openImageModal(this.src)">
                                            @endif
                                            @if (!$user->aadhar_front && !$user->aadhar_back && !$user->pan_image)
                                                <span class="text-muted">No docs</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($user->is_verified)
                                            <span class="badge bg-success">Verified</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Not Verified</span>
                                        @endif
                                    </td>
                                    <td id="completed-badge-{{ $user->id }}">
                                        @if ($user->is_completed)
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-secondary">Incomplete</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox"
                                                data-id="{{ $user->id }}" {{ $user->is_completed ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>{{ $user->created_at ? $user->created_at->format('d-m-Y') : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-primary"
                                            title="Check Aadhaar Details">
                                            <i class="fas fa-id-card"></i> Check Aadhaar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No users found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        {{ $users->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Document Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Document" style="max-width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        // Image Modal Function
        function openImageModal(imageSrc) {
            $('#modalImage').attr('src', imageSrc);
            $('#imageModal').modal('show');
        }

        $(document).ready(function() {
            // Per Page Selector
            $('#perPageSelect').on('change', function() {
                let perPage = $(this).val();
                let url = new URL(window.location.href);
                url.searchParams.set('per_page', perPage);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            });

            // Status Toggle
            $('.status-toggle').on('change', function() {
                const userId = $(this).data('id');
                const isChecked = $(this).is(':checked');
                const toggleElement = $(this);

                $.ajax({
                    url: `/users/${userId}/toggle-status`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        toggleElement.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);

                            const badgeCell = $(`#completed-badge-${userId}`);
                            if (response.status) {
                                badgeCell.html(
                                    '<span class="badge bg-success">Completed</span>');
                            } else {
                                badgeCell.html(
                                    '<span class="badge bg-secondary">Incomplete</span>');
                            }
                        } else {
                            toastr.error('Failed to update status');
                            toggleElement.prop('checked', !isChecked);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error updating status');
                        toggleElement.prop('checked', !isChecked);
                        console.error('Error:', xhr.responseText);
                    },
                    complete: function() {
                        toggleElement.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection
