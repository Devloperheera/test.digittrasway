@extends('Website.Layout.master')

@section('custom_css')
<style>
    @media print {
        .no-print { display: none !important; }
        .details-card { box-shadow: none; border: 1px solid #ddd; }
    }

    .details-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        padding: 30px;
        margin-bottom: 20px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }

    .verified-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 50px;
        display: inline-block;
        font-weight: bold;
    }

    .section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin: 30px 0 20px 0;
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

    .info-value.text-muted {
        font-style: italic;
        color: #999 !important;
    }

    .action-buttons {
        margin: 20px 0;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .profile-section {
        text-align: center;
        margin: 20px 0;
        padding: 20px;
        background: #f5f5f5;
        border-radius: 10px;
    }

    .profile-image {
        max-width: 200px;
        border: 3px solid #667eea;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
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

    .alert-info-custom {
        background: #e3f2fd;
        border-left: 4px solid #2196F3;
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
    }
</style>
@endsection

@section('content')
<div class="container mt-4">
    {{-- Action Buttons --}}
    <div class="action-buttons no-print">
        <a href="{{ route('document-verification.index') }}" class="btn btn-primary">
            <i class="fas fa-search"></i> New Search
        </a>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print
        </button>
        <form method="POST" action="{{ route('document-verification.export-dl-pdf') }}" style="display: inline;">
            @csrf
            <input type="hidden" name="dl_data" value='@json($data)'>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </form>
    </div>

    <div class="details-card">
        <div class="text-center mb-4">
            <h2><i class="fas fa-id-card text-primary me-2"></i>Driving License Verification Details</h2>
            <span class="verified-badge"><i class="fas fa-check-circle"></i> Government Verified</span>
            @if(isset($verified_at))
                <p class="text-muted mt-2">Verified on: {{ $verified_at }}</p>
            @endif
        </div>
        <hr>

        {{-- Limited Info Warning --}}
        @if(isset($data['less_info']) && $data['less_info'])
        <div class="alert-info-custom no-print">
            <i class="fas fa-info-circle"></i> <strong>Limited Information:</strong>
            Some details are not available due to privacy restrictions from Government database.
        </div>
        @endif

        {{-- Profile Image --}}
        @if(isset($data['has_image']) && $data['has_image'] && !empty($data['profile_image']))
        <div class="profile-section no-print">
            <h5>License Holder Photo</h5>
            <img src="data:image/jpeg;base64,{{ $data['profile_image'] }}" class="profile-image" alt="Profile Photo">
        </div>
        @endif

        {{-- Basic DL Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-id-card-alt me-2"></i>Basic DL Information</h5>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">License Number</div>
                    <div class="info-value"><strong class="text-primary">{{ $data['license_number'] ?? $dl_number ?? 'N/A' }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">State</div>
                    <div class="info-value">
                        @if(!empty($data['state']))
                            {{ $data['state'] }}
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">RTO Office Name</div>
                    <div class="info-value">
                        @if(!empty($data['ola_name']))
                            {{ $data['ola_name'] }}
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">RTO Code</div>
                    <div class="info-value">
                        @if(!empty($data['ola_code']))
                            {{ $data['ola_code'] }}
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Client ID</div>
                    <div class="info-value"><code>{{ $data['client_id'] ?? 'N/A' }}</code></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Citizenship</div>
                    <div class="info-value">{{ $data['citizenship'] ?? 'Indian' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Has Profile Image</div>
                    <div class="info-value">
                        @if(isset($data['has_image']) && $data['has_image'])
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Data Availability</div>
                    <div class="info-value">
                        @if(isset($data['less_info']) && $data['less_info'])
                            <span class="badge bg-warning">Limited</span>
                        @else
                            <span class="badge bg-success">Complete</span>
                        @endif
                    </div>
                </div>
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
                    <div class="info-value"><strong>{{ $data['name'] ?? 'N/A' }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Father/Husband Name</div>
                    <div class="info-value">
                        @if(!empty($data['father_or_husband_name']))
                            {{ $data['father_or_husband_name'] }}
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value">{{ $data['dob'] ?? $dob ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Gender</div>
                    <div class="info-value">
                        @php
                            $gender = $data['gender'] ?? '';
                            if ($gender === 'M' || $gender === 'MALE') $gender = 'Male';
                            elseif ($gender === 'F' || $gender === 'FEMALE') $gender = 'Female';
                            elseif ($gender === 'X' || $gender === 'O' || $gender === 'OTHER') $gender = 'Other';
                            elseif (empty($gender)) $gender = null;
                        @endphp
                        @if($gender)
                            {{ $gender }}
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Blood Group</div>
                    <div class="info-value">
                        @if(!empty($data['blood_group']) && $data['blood_group'] !== 'N/A')
                            <span class="badge bg-danger">{{ $data['blood_group'] }}</span>
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Address Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h5>
        </div>

        @php
            $hasPermanentAddress = !empty($data['permanent_address']) && trim($data['permanent_address']) !== '';
            $hasTemporaryAddress = !empty($data['temporary_address']) && trim($data['temporary_address']) !== '';
            $hasPermanentZip = !empty($data['permanent_zip']) && trim($data['permanent_zip']) !== '';
            $hasTemporaryZip = !empty($data['temporary_zip']) && trim($data['temporary_zip']) !== '';
        @endphp

        @if(!$hasPermanentAddress && !$hasTemporaryAddress)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Address Not Available:</strong>
            Address details are not available from Government database due to privacy restrictions.
        </div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Permanent Address</div>
                    <div class="info-value">
                        @if($hasPermanentAddress)
                            {{ $data['permanent_address'] }}
                        @else
                            <span class="text-muted">Not Available in Government Database</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Permanent PIN Code</div>
                    <div class="info-value">
                        @if($hasPermanentZip)
                            <code>{{ $data['permanent_zip'] }}</code>
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Temporary Address</div>
                    <div class="info-value">
                        @if($hasTemporaryAddress)
                            {{ $data['temporary_address'] }}
                        @else
                            <span class="text-muted">Not Available in Government Database</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Temporary PIN Code</div>
                    <div class="info-value">
                        @if($hasTemporaryZip)
                            <code>{{ $data['temporary_zip'] }}</code>
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- License Validity --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>License Validity Information</h5>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Date of Issue (Non-Transport)</div>
                    <div class="info-value">
                        @if(!empty($data['doi']) && $data['doi'] !== '1800-01-01')
                            {{ $data['doi'] }}
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Expiry (Non-Transport)</div>
                    <div class="info-value">
                        @php
                            $doe = $data['doe'] ?? null;
                            $isExpired = false;
                            if ($doe && $doe !== '1800-01-01') {
                                try {
                                    $isExpired = \Carbon\Carbon::parse($doe)->isPast();
                                } catch (\Exception $e) {
                                    $doe = null;
                                }
                            } else {
                                $doe = null;
                            }
                        @endphp
                        @if($doe)
                            {{ $doe }}
                            @if($isExpired)
                                <span class="badge bg-danger ms-2">Expired</span>
                            @else
                                <span class="badge bg-success ms-2">Valid</span>
                            @endif
                        @else
                            <span class="text-muted">Not Available</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Date of Issue (Transport)</div>
                    <div class="info-value">
                        @if(isset($data['transport_doi']) && $data['transport_doi'] !== '1800-01-01')
                            {{ $data['transport_doi'] }}
                        @else
                            <span class="text-muted">Not Applicable</span>
                        @endif
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Expiry (Transport)</div>
                    <div class="info-value">
                        @if(isset($data['transport_doe']) && $data['transport_doe'] !== '1800-01-01')
                            {{ $data['transport_doe'] }}
                        @else
                            <span class="text-muted">Not Applicable</span>
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
            @if(isset($data['vehicle_classes']) && is_array($data['vehicle_classes']) && count($data['vehicle_classes']) > 0)
                @foreach($data['vehicle_classes'] as $class)
                    <span class="class-badge">{{ $class }}</span>
                @endforeach
            @else
                <span class="text-muted">No vehicle classes available</span>
            @endif
        </div>

        {{-- Footer --}}
        <div class="text-center mt-4 text-muted">
            <small><i class="fas fa-shield-alt"></i> Data verified from Government Database via SurePass API</small>
            @if(isset($data['less_info']) && $data['less_info'])
                <br><small><i class="fas fa-info-circle"></i> Some details are restricted due to privacy regulations</small>
            @endif
        </div>
    </div>
</div>
@endsection
