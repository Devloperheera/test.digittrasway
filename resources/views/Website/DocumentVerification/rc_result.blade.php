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
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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

    .action-buttons {
        margin: 20px 0;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
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
        <form method="POST" action="{{ route('document-verification.export-rc-pdf') }}" style="display: inline;">
            @csrf
            <input type="hidden" name="rc_data" value='@json($data)'>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </form>
    </div>

    <div class="details-card">
        <div class="text-center mb-4">
            <h2><i class="fas fa-file-alt text-primary me-2"></i>RC Verification Details</h2>
            <span class="verified-badge"><i class="fas fa-check-circle"></i> Government Verified</span>
            @if(isset($verified_at))
                <p class="text-muted mt-2">Verified on: {{ $verified_at }}</p>
            @endif
        </div>
        <hr>

        {{-- Basic RC Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Basic RC Information</h5>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">RC Number</div>
                    <div class="info-value"><strong class="text-primary">{{ $data['rc_number'] ?? $rc_number ?? 'N/A' }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Registration Date</div>
                    <div class="info-value">{{ $data['registration_date'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Registered At (RTO)</div>
                    <div class="info-value">{{ $data['registered_at'] ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">RC Status</div>
                    <div class="info-value">
                        <span class="badge bg-success">{{ $data['rc_status'] ?? 'Active' }}</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Owner Number</div>
                    <div class="info-value">{{ $data['owner_number'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Client ID</div>
                    <div class="info-value"><code>{{ $data['client_id'] ?? 'N/A' }}</code></div>
                </div>
            </div>
        </div>

        {{-- Owner Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Owner Information</h5>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Owner Name</div>
                    <div class="info-value"><strong>{{ $data['owner_name'] ?? 'N/A' }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Father Name</div>
                    <div class="info-value">{{ $data['father_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Mobile Number</div>
                    <div class="info-value">{{ $data['mobile_number'] ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Present Address</div>
                    <div class="info-value">{{ $data['present_address'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Permanent Address</div>
                    <div class="info-value">{{ $data['permanent_address'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Vehicle Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-car me-2"></i>Vehicle Information</h5>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Vehicle Category</div>
                    <div class="info-value">{{ $data['vehicle_category'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Category Description</div>
                    <div class="info-value">{{ $data['vehicle_category_description'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Maker Description</div>
                    <div class="info-value">{{ $data['maker_description'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Maker Model</div>
                    <div class="info-value"><strong>{{ $data['maker_model'] ?? 'N/A' }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Body Type</div>
                    <div class="info-value">{{ $data['body_type'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Color</div>
                    <div class="info-value">{{ $data['color'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fuel Type</div>
                    <div class="info-value">{{ $data['fuel_type'] ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Chassis Number</div>
                    <div class="info-value"><code>{{ $data['vehicle_chasi_number'] ?? $data['chassis_number'] ?? 'N/A' }}</code></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Engine Number</div>
                    <div class="info-value"><code>{{ $data['vehicle_engine_number'] ?? $data['engine_number'] ?? 'N/A' }}</code></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Norms Type</div>
                    <div class="info-value">{{ $data['norms_type'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Manufacturing Date</div>
                    <div class="info-value">{{ $data['manufacturing_date'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Cubic Capacity</div>
                    <div class="info-value">{{ $data['cubic_capacity'] ?? 'N/A' }} {{ !empty($data['cubic_capacity']) ? 'CC' : '' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Seat Capacity</div>
                    <div class="info-value">{{ $data['seat_capacity'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Insurance Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Insurance Information</h5>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Insurance Company</div>
                    <div class="info-value">{{ $data['insurance_company'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Insurance Policy Number</div>
                    <div class="info-value"><code>{{ $data['insurance_policy_number'] ?? 'N/A' }}</code></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Insurance Valid Upto</div>
                    <div class="info-value">{{ $data['insurance_upto'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Finance Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-university me-2"></i>Finance Information</h5>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Financed</div>
                    <div class="info-value">
                        @if(isset($data['financed']) && $data['financed'])
                            <span class="badge bg-warning">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Financer</div>
                    <div class="info-value">{{ $data['financer'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Permit Information --}}
        <div class="section-header">
            <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Permit Information</h5>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="info-row">
                    <div class="info-label">Permit Number</div>
                    <div class="info-value">{{ $data['permit_number'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Permit Type</div>
                    <div class="info-value">{{ $data['permit_type'] ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-row">
                    <div class="info-label">Permit Valid From</div>
                    <div class="info-value">{{ $data['permit_valid_from'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Permit Valid Upto</div>
                    <div class="info-value">{{ $data['permit_valid_upto'] ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-row">
                    <div class="info-label">Tax Paid Upto</div>
                    <div class="info-value">{{ $data['tax_paid_upto'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fit Up To</div>
                    <div class="info-value">{{ $data['fit_up_to'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-4 text-muted">
            <small><i class="fas fa-shield-alt"></i> Data verified from Government Database via SurePass API</small>
        </div>
    </div>
</div>
@endsection
