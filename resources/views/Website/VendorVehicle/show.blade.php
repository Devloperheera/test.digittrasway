@extends('Website.Layout.master')

@section('custom_css')
<style>
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .vehicle-main-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 10px;
    }

    .document-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
    }

    .info-row {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }

    .info-row:last-child {
        border-bottom: none;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-truck me-2"></i>
                Vehicle Details
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('vendor-vehicles.index') }}">
                            <i class="fas fa-truck"></i> Vehicles
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ $vehicle->vehicle_registration_number }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('vendor-vehicles.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            @if($vehicle->status === 'pending')
                <button class="btn btn-success" onclick="approveVehicle()">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="fas fa-times"></i> Reject
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Vehicle Image & Basic Info -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    @if($vehicle->vehicle_image)
                        <img src="{{ asset('storage/' . $vehicle->vehicle_image) }}"
                             class="vehicle-main-image"
                             alt="Vehicle">
                    @else
                        <div class="vehicle-main-image bg-secondary d-flex align-items-center justify-content-center text-white">
                            <i class="fas fa-truck fa-5x"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <strong>Vehicle Name:</strong> {{ $vehicle->vehicle_name ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Registration Number:</strong> <code>{{ $vehicle->vehicle_registration_number }}</code>
                    </div>
                    <div class="info-row">
                        <strong>Category:</strong>
                        <span class="badge bg-info">{{ $vehicle->category->category_name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Model:</strong> {{ $vehicle->model->model_name ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Color:</strong> {{ $vehicle->vehicle_color }}
                    </div>
                    <div class="info-row">
                        <strong>Manufacturing Year:</strong> {{ $vehicle->manufacturing_year }}
                    </div>
                    <div class="info-row">
                        <strong>Status:</strong> {!! $vehicle->status_badge !!}
                    </div>
                    <div class="info-row">
                        <strong>Availability:</strong> {!! $vehicle->availability_badge !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Details -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Vendor Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Name:</strong>
                        <a href="{{ route('vendors.show', $vehicle->vendor->id) }}">
                            {{ $vehicle->vendor->name }}
                        </a>
                    </div>
                    <div class="info-row">
                        <strong>Contact:</strong> {{ $vehicle->vendor->contact_number }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Email:</strong> {{ $vehicle->vendor->email ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>City:</strong> {{ $vehicle->vendor->city ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Verified:</strong>
                        @if($vehicle->vendor->is_verified)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-danger">No</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Technical Details -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Technical Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Chassis Number:</strong> <code>{{ $vehicle->chassis_number ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>Engine Number:</strong> <code>{{ $vehicle->engine_number ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>Owner Name:</strong> {{ $vehicle->owner_name ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Has GPS:</strong>
                        @if($vehicle->has_gps)
                            <span class="badge bg-success"><i class="fas fa-check"></i> Yes</span>
                        @else
                            <span class="badge bg-secondary"><i class="fas fa-times"></i> No</span>
                        @endif
                    </div>
                    <div class="info-row">
                        <strong>Has Insurance:</strong>
                        @if($vehicle->has_insurance)
                            <span class="badge bg-success"><i class="fas fa-check"></i> Yes</span>
                        @else
                            <span class="badge bg-secondary"><i class="fas fa-times"></i> No</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Verification -->
    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Document Verification</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>RC Number:</strong> <code>{{ $vehicle->rc_number ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>RC Verified:</strong>
                        @if($vehicle->rc_verified)
                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                        @endif
                    </div>
                    @if($vehicle->rc_verification_date)
                        <div class="info-row">
                            <strong>Verified On:</strong> {{ $vehicle->rc_verification_date->format('d M Y') }}
                        </div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Insurance Number:</strong> {{ $vehicle->insurance_number ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Insurance Expiry:</strong>
                        @if($vehicle->insurance_expiry)
                            {{ $vehicle->insurance_expiry->format('d M Y') }}
                            @if($vehicle->insurance_expiry < now())
                                <span class="badge bg-danger">Expired</span>
                            @endif
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Fitness Expiry:</strong>
                        @if($vehicle->fitness_expiry)
                            {{ $vehicle->fitness_expiry->format('d M Y') }}
                            @if($vehicle->fitness_expiry < now())
                                <span class="badge bg-danger">Expired</span>
                            @endif
                        @else
                            N/A
                        @endif
                    </div>
                    <div class="info-row">
                        <strong>Permit Expiry:</strong>
                        @if($vehicle->permit_expiry)
                            {{ $vehicle->permit_expiry->format('d M Y') }}
                            @if($vehicle->permit_expiry < now())
                                <span class="badge bg-danger">Expired</span>
                            @endif
                        @else
                            N/A
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Images -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-images me-2"></i>Document Images</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @if($vehicle->rc_front_image)
                <div class="col-md-3 mb-3">
                    <h6>RC Front</h6>
                    <img src="{{ asset('storage/' . $vehicle->rc_front_image) }}"
                         class="document-image"
                         alt="RC Front"
                         onclick="showImage(this.src)">
                </div>
                @endif

                @if($vehicle->rc_back_image)
                <div class="col-md-3 mb-3">
                    <h6>RC Back</h6>
                    <img src="{{ asset('storage/' . $vehicle->rc_back_image) }}"
                         class="document-image"
                         alt="RC Back"
                         onclick="showImage(this.src)">
                </div>
                @endif

                @if($vehicle->insurance_image)
                <div class="col-md-3 mb-3">
                    <h6>Insurance</h6>
                    <img src="{{ asset('storage/' . $vehicle->insurance_image) }}"
                         class="document-image"
                         alt="Insurance"
                         onclick="showImage(this.src)">
                </div>
                @endif

                @if($vehicle->dl_image)
                <div class="col-md-3 mb-3">
                    <h6>Driving License</h6>
                    <img src="{{ asset('storage/' . $vehicle->dl_image) }}"
                         class="document-image"
                         alt="DL"
                         onclick="showImage(this.src)">
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Performance Stats -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Performance Statistics</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <h4>{{ $vehicle->completed_trips ?? 0 }}</h4>
                    <p class="text-muted">Completed Trips</p>
                </div>
                <div class="col-md-3">
                    <h4>{{ $vehicle->cancelled_trips ?? 0 }}</h4>
                    <p class="text-muted">Cancelled Trips</p>
                </div>
                <div class="col-md-3">
                    <h4>{{ $vehicle->average_rating ?? 0 }} <i class="fas fa-star text-warning"></i></h4>
                    <p class="text-muted">Average Rating</p>
                </div>
                <div class="col-md-3">
                    <h4>{{ $vehicle->total_ratings ?? 0 }}</h4>
                    <p class="text-muted">Total Ratings</p>
                </div>
            </div>
        </div>
    </div>

    @if($vehicle->rejection_reason)
    <div class="alert alert-danger">
        <h6><i class="fas fa-exclamation-triangle me-2"></i>Rejection Reason</h6>
        <p class="mb-0">{{ $vehicle->rejection_reason }}</p>
    </div>
    @endif
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('vendor-vehicles.reject', $vehicle->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  name="rejection_reason"
                                  rows="4"
                                  required></textarea>
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
function approveVehicle() {
    if(confirm('Are you sure you want to approve this vehicle?')) {
        window.location.href = '{{ route("vendor-vehicles.approve", $vehicle->id) }}';
    }
}

function showImage(src) {
    window.open(src, '_blank');
}
</script>
@endsection
