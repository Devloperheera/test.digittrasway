@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .details-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 20px;
        }

        .verified-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            font-weight: bold;
        }

        .info-row {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #333;
        }

        .vehicle-classes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .class-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 30px 0 20px 0;
        }

        .profile-image {
            max-width: 200px;
            border-radius: 10px;
            border: 3px solid #667eea;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="table-section" class="table-container">
            <div class="row mb-3">
                <div class="col-md-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('vendors.index') }}">Vendors</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('vendors.show', $vendor->id) }}">{{ $vendor->name }}</a></li>
                            <li class="breadcrumb-item active">DL Details</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="details-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>
                            <i class="fas fa-id-card text-primary me-2"></i>
                            Driving License Verification Details
                        </h2>
                        <span class="verified-badge">
                            <i class="fas fa-check-circle"></i> Government Verified
                        </span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('vendors.dl-export.excel', $vendor->id) }}">
                                    <i class="fas fa-file-excel text-success"></i> Export as Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('vendors.dl-export.csv', $vendor->id) }}">
                                    <i class="fas fa-file-csv text-info"></i> Export as CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('vendors.dl-export.pdf', $vendor->id) }}">
                                    <i class="fas fa-file-pdf text-danger"></i> Export as PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <hr>

                {{-- Vendor Information --}}
                <h4 class="mb-3"><i class="fas fa-user me-2"></i>Vendor Information</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Vendor Name</div>
                            <div class="info-value">{{ $vendor->name }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value">{{ $vendor->contact_number }}</div>
                        </div>
                    </div>
                </div>

                {{-- Basic DL Information --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-id-card-alt me-2"></i>Basic DL Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">Client ID</div>
                                    <div class="info-value">{{ $dlData['client_id'] ?? 'N/A' }}</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">License Number</div>
                                    <div class="info-value"><strong
                                            class="text-primary">{{ $dlData['license_number'] ?? ($dlData['dl_number'] ?? 'N/A') }}</strong>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">State</div>
                                    <div class="info-value">{{ $dlData['state'] ?? 'N/A' }}</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Has Image</div>
                                    <div class="info-value">
                                        @if (isset($dlData['has_image']) && $dlData['has_image'])
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-row">
                                    <div class="info-label">OLA Name</div>
                                    <div class="info-value">{{ $dlData['ola_name'] ?? 'N/A' }}</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">OLA Code</div>
                                    <div class="info-value">{{ $dlData['ola_code'] ?? 'N/A' }}</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Citizenship</div>
                                    <div class="info-value">
                                        {{ !empty($dlData['citizenship']) ? $dlData['citizenship'] : 'N/A' }}</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Less Info</div>
                                    <div class="info-value">
                                        @if (isset($dlData['less_info']) && $dlData['less_info'])
                                            <span class="badge bg-warning">Yes</span>
                                        @else
                                            <span class="badge bg-success">No</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        @if (isset($dlData['profile_image']) && !empty($dlData['profile_image']) && $dlData['has_image'])
                            <div class="info-label mb-2">Profile Photo</div>
                            <img src="data:image/jpeg;base64,{{ $dlData['profile_image'] }}" class="profile-image"
                                alt="DL Profile Photo">
                        @else
                            <div class="text-muted">
                                <i class="fas fa-user-circle fa-5x"></i>
                                <p class="mt-2">No Photo Available</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Personal Information --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Personal Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><strong>{{ $dlData['name'] ?? 'N/A' }}</strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Father/Husband Name</div>
                            <div class="info-value">{{ $dlData['father_or_husband_name'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value">{{ $dlData['dob'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Gender</div>
                            <div class="info-value">
                                @php
                                    $gender = $dlData['gender'] ?? 'N/A';
                                    if ($gender === 'M') {
                                        $gender = 'Male';
                                    } elseif ($gender === 'F') {
                                        $gender = 'Female';
                                    } elseif ($gender === 'O') {
                                        $gender = 'Other';
                                    }
                                @endphp
                                {{ $gender }}
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Blood Group</div>
                            <div class="info-value">{{ !empty($dlData['blood_group']) ? $dlData['blood_group'] : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Address Information --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Permanent Address</div>
                            <div class="info-value">{{ $dlData['permanent_address'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Permanent ZIP</div>
                            <div class="info-value">
                                {{ !empty($dlData['permanent_zip']) ? $dlData['permanent_zip'] : 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Temporary Address</div>
                            <div class="info-value">{{ $dlData['temporary_address'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Temporary ZIP</div>
                            <div class="info-value">
                                {{ !empty($dlData['temporary_zip']) ? $dlData['temporary_zip'] : 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- License Dates --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>License Validity Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Date of Issue (Non-Transport)</div>
                            <div class="info-value">{{ $dlData['doi'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Expiry (Non-Transport)</div>
                            <div class="info-value">{{ $dlData['doe'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Date of Issue (Transport)</div>
                            <div class="info-value">
                                @if (isset($dlData['transport_doi']) && $dlData['transport_doi'] !== '1800-01-01')
                                    {{ $dlData['transport_doi'] }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Expiry (Transport)</div>
                            <div class="info-value">
                                @if (isset($dlData['transport_doe']) && $dlData['transport_doe'] !== '1800-01-01')
                                    {{ $dlData['transport_doe'] }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vehicle Classes --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-car me-2"></i>Authorized Vehicle Classes</h5>
                </div>
                <div class="vehicle-classes">
                    @if (isset($dlData['vehicle_classes']) && is_array($dlData['vehicle_classes']) && count($dlData['vehicle_classes']) > 0)
                        @foreach ($dlData['vehicle_classes'] as $class)
                            <span class="class-badge">{{ $class }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">No vehicle classes available</span>
                    @endif
                </div>

                {{-- Verification Status --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Verification Status</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Verified At</div>
                            <div class="info-value">{{ $dlData['verified_at'] ?? now()->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Government Verified</div>
                            <div class="info-value">
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Yes - Via SurePass
                                    API</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('vendors.show', $vendor->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Vendor
                    </a>
                    <a href="{{ route('vendors.index') }}" class="btn btn-primary">
                        <i class="fas fa-list"></i> Back to Vendors List
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
