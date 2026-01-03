@extends('Website.Layout.master')

@section('custom_css')
<style>
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .card-header {
        border-radius: 10px 10px 0 0 !important;
        padding: 15px 20px;
    }

    .vendor-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
    }

    .info-row {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .document-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
    }

    .verified-badge {
        background-color: #4CAF50;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
    }

    .pending-badge {
        background-color: #FFC107;
        color: #333;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-user me-2"></i>
                Vendor Details
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('vendors.index') }}">
                            <i class="fas fa-users"></i> Vendors
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ $vendor->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('vendors.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Vendor Summary -->
    <div class="vendor-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-3">
                    <i class="fas fa-user-circle me-2"></i>
                    {{ $vendor->name }}
                </h3>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>📱 Contact:</strong> {{ $vendor->contact_number }}</p>
                        <p><strong>📧 Email:</strong> {{ $vendor->email ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>🏙️ City:</strong> {{ $vendor->city ?? 'N/A' }}</p>
                        <p><strong>📍 State:</strong> {{ $vendor->state ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>📅 DOB:</strong> {{ $vendor->dob ? $vendor->dob->format('d M Y') : 'N/A' }}</p>
                        <p><strong>⚧️ Gender:</strong> {{ $vendor->gender ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-white rounded p-3 text-dark">
                    <h6>Verification Status</h6>
                    @if($vendor->is_verified)
                        <span class="verified-badge">
                            <i class="fas fa-check-circle"></i> Verified
                        </span>
                    @else
                        <span class="pending-badge">
                            <i class="fas fa-clock"></i> Pending
                        </span>
                    @endif
                    <hr>
                    <small>Member Since: {{ $vendor->created_at->format('d M Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Details -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Full Name:</strong> {{ $vendor->name }}
                    </div>
                    <div class="info-row">
                        <strong>Contact Number:</strong> {{ $vendor->contact_number }}
                    </div>
                    <div class="info-row">
                        <strong>Email:</strong> {{ $vendor->email ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Date of Birth:</strong> {{ $vendor->dob ? $vendor->dob->format('d M Y') : 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Gender:</strong> {{ $vendor->gender ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Emergency Contact:</strong> {{ $vendor->emergency_contact ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Full Address:</strong> {{ $vendor->full_address ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>City:</strong> {{ $vendor->city ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>State:</strong> {{ $vendor->state ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Pincode:</strong> {{ $vendor->pincode ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Information -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-truck me-2"></i>Vehicle Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Vehicle Category:</strong>
                        {{ $vendor->vehicleCategory->category_name ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Vehicle Model:</strong>
                        {{ $vendor->vehicleModel->model_name ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Registration Number:</strong>
                        <code>{{ $vendor->vehicle_registration_number ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>Vehicle Type:</strong> {{ $vendor->vehicle_type ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Vehicle Listed:</strong>
                        @if($vendor->vehicle_listed)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                    <div class="info-row">
                        <strong>Vehicle Status:</strong>
                        <span class="badge bg-info">{{ $vendor->vehicle_status ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Availability:</strong>
                        <span class="badge bg-primary">{{ $vendor->availability_status ?? 'N/A' }}</span>
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
                <!-- RC Verification -->
                <div class="col-md-6">
                    <h6><i class="fas fa-car me-2"></i>RC (Registration Certificate)</h6>
                    <div class="info-row">
                        <strong>RC Number:</strong> <code>{{ $vendor->rc_number ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>RC Verified:</strong>
                        @if($vendor->rc_verified)
                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                        @endif
                    </div>
                    @if($vendor->rc_verification_date)
                        <div class="info-row">
                            <strong>Verified On:</strong> {{ $vendor->rc_verification_date->format('d M Y, h:i A') }}
                        </div>
                    @endif
                    @if($vendor->rc_verified && $vendor->rc_verified_data)
                        <div class="mt-3">
                            <a href="{{ route('vendors.rc-details', $vendor->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View Full RC Details
                            </a>
                        </div>
                    @endif
                </div>

                <!-- DL Verification -->
                <div class="col-md-6">
                    <h6><i class="fas fa-id-card me-2"></i>DL (Driving License)</h6>
                    <div class="info-row">
                        <strong>DL Number:</strong> <code>{{ $vendor->dl_number ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>DL Verified:</strong>
                        @if($vendor->dl_verified)
                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                        @endif
                    </div>
                    @if($vendor->dl_verification_date)
                        <div class="info-row">
                            <strong>Verified On:</strong> {{ $vendor->dl_verification_date->format('d M Y, h:i A') }}
                        </div>
                    @endif
                    @if($vendor->dl_verified && $vendor->dl_verified_data)
                        <div class="mt-3">
                            <a href="{{ route('vendors.dl-details', $vendor->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View Full DL Details
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Document Images -->
    @if($vendor->aadhar_front || $vendor->pan_image || $vendor->rc_image || $vendor->dl_image)
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-images me-2"></i>Document Images</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @if($vendor->aadhar_front)
                <div class="col-md-3 mb-3">
                    <h6>Aadhaar Front</h6>
                    <img src="{{ asset('storage/' . $vendor->aadhar_front) }}"
                         class="document-image"
                         alt="Aadhaar Front"
                         onclick="showImage(this.src)">
                </div>
                @endif

                @if($vendor->aadhar_back)
                <div class="col-md-3 mb-3">
                    <h6>Aadhaar Back</h6>
                    <img src="{{ asset('storage/' . $vendor->aadhar_back) }}"
                         class="document-image"
                         alt="Aadhaar Back"
                         onclick="showImage(this.src)">
                </div>
                @endif

                @if($vendor->pan_image)
                <div class="col-md-3 mb-3">
                    <h6>PAN Card</h6>
                    <img src="{{ asset('storage/' . $vendor->pan_image) }}"
                         class="document-image"
                         alt="PAN"
                         onclick="showImage(this.src)">
                </div>
                @endif

                @if($vendor->rc_image)
                <div class="col-md-3 mb-3">
                    <h6>RC Document</h6>
                    <img src="{{ asset('storage/' . $vendor->rc_image) }}"
                         class="document-image"
                         alt="RC"
                         onclick="showImage(this.src)">
                </div>
                @endif

                @if($vendor->dl_image)
                <div class="col-md-3 mb-3">
                    <h6>Driving License</h6>
                    <img src="{{ asset('storage/' . $vendor->dl_image) }}"
                         class="document-image"
                         alt="DL"
                         onclick="showImage(this.src)">
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Bank Details -->
    @if($vendor->bank_name || $vendor->account_number)
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-university me-2"></i>Bank Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Bank Name:</strong> {{ $vendor->bank_name ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Account Number:</strong> {{ $vendor->account_number ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>IFSC Code:</strong> {{ $vendor->ifsc ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Back Button -->
    <div class="text-center mb-4 mt-3">
        <a href="{{ route('vendors.index') }}" class="btn btn-lg btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Vendors List
        </a>
    </div>
</div>
@endsection

@section('custom_js')
<script>
function showImage(src) {
    window.open(src, '_blank');
}
</script>
@endsection
