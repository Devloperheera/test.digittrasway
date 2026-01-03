@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .badge {
            padding: 5px 10px;
            font-size: 12px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .vehicle-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="table-section" class="table-container">
            <div class="row mb-4 ">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-truck me-2"></i>
                        Vendor Vehicles Management
                    </h2>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h3>{{ $vehicles->where('status', 'approved')->count() }}</h3>
                            <p class="mb-0">Approved Vehicles</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h3>{{ $vehicles->where('status', 'pending')->count() }}</h3>
                            <p class="mb-0">Pending Approval</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3>{{ $vehicles->where('is_available', true)->count() }}</h3>
                            <p class="mb-0">Available</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3>{{ $vehicles->where('is_listed', true)->count() }}</h3>
                            <p class="mb-0">Listed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('vendor-vehicles.index') }}">
                <div class="row g-3 mb-5">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Registration, RC, Vendor..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                            </option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>
                                Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>
                                Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Availability</label>
                        <select name="is_available" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('is_available') == '1' ? 'selected' : '' }}>Available
                            </option>
                            <option value="0" {{ request('is_available') == '0' ? 'selected' : '' }}>
                                Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('vendor-vehicles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th>
                            <th>Vehicle Info</th>
                            <th>Vendor</th>
                            <th>Category</th>
                            <th>Registration</th>
                            <th>RC Verified</th>
                            <th>Status</th>
                            <th>Listed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicles as $vehicle)
                            <tr>
                                <td>
                                    @if ($vehicle->vehicle_image)
                                        <img src="{{ asset('storage/' . $vehicle->vehicle_image) }}" class="vehicle-image"
                                            alt="Vehicle">
                                    @else
                                        <div
                                            class="vehicle-image bg-secondary d-flex align-items-center justify-content-center text-white">
                                            <i class="fas fa-truck fa-2x"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $vehicle->vehicle_name ?? 'N/A' }}</strong><br>
                                    <small class="text-muted">{{ $vehicle->model->model_name ?? 'N/A' }}</small><br>
                                    <small class="text-muted">{{ $vehicle->vehicle_color }} |
                                        {{ $vehicle->manufacturing_year }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('vendors.show', $vehicle->vendor_id) }}" class="text-primary">
                                        {{ $vehicle->vendor->name ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $vehicle->category->category_name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td><code>{{ $vehicle->vehicle_registration_number }}</code></td>
                                <td>
                                    @if ($vehicle->rc_verified)
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Verified</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i>
                                            Pending</span>
                                    @endif
                                </td>
                                <td>{!! $vehicle->status_badge !!}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input listing-toggle" type="checkbox"
                                            data-id="{{ $vehicle->id }}" {{ $vehicle->is_listed ? 'checked' : '' }}
                                            {{ $vehicle->status !== 'approved' ? 'disabled' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('vendor-vehicles.show', $vehicle->id) }}"
                                            class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ($vehicle->status === 'pending')
                                            <button type="button" class="btn btn-sm btn-success approve-btn"
                                                data-id="{{ $vehicle->id }}" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger reject-btn"
                                                data-id="{{ $vehicle->id }}" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                        <form action="{{ route('vendor-vehicles.destroy', $vehicle->id) }}"
                                            method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No vehicles found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $vehicles->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="rejection_reason" rows="4" required
                                placeholder="Enter reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        $(document).ready(function() {
            // Listing Toggle
            $('.listing-toggle').on('change', function() {
                const vehicleId = $(this).data('id');
                const isChecked = $(this).is(':checked');
                const toggleElement = $(this);

                $.ajax({
                    url: `/vendor-vehicles/${vehicleId}/toggle-listing`,
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
                        } else {
                            toastr.error('Failed to update listing');
                            toggleElement.prop('checked', !isChecked);
                        }
                    },
                    error: function() {
                        toastr.error('Error updating listing');
                        toggleElement.prop('checked', !isChecked);
                    },
                    complete: function() {
                        toggleElement.prop('disabled', false);
                    }
                });
            });

            // Approve Vehicle
            $('.approve-btn').on('click', function() {
                const vehicleId = $(this).data('id');

                if (confirm('Are you sure you want to approve this vehicle?')) {
                    window.location.href = `/vendor-vehicles/${vehicleId}/approve`;
                }
            });

            // Reject Vehicle
            $('.reject-btn').on('click', function() {
                const vehicleId = $(this).data('id');
                $('#rejectForm').attr('action', `/vendor-vehicles/${vehicleId}/reject`);
                $('#rejectModal').modal('show');
            });
        });
    </script>
@endsection
