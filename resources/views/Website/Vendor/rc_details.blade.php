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
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 30px 0 20px 0;
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
                            <li class="breadcrumb-item active">RC Details</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="details-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>
                            <i class="fas fa-file-alt text-primary me-2"></i>
                            RC Verification Details
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
                                <a class="dropdown-item" href="{{ route('vendors.rc-export.excel', $vendor->id) }}">
                                    <i class="fas fa-file-excel text-success"></i> Export as Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('vendors.rc-export.csv', $vendor->id) }}">
                                    <i class="fas fa-file-csv text-info"></i> Export as CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('vendors.rc-export.pdf', $vendor->id) }}">
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

                {{-- Basic RC Information --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Basic RC Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Client ID</div>
                            <div class="info-value">{{ $rcData['client_id'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">RC Number</div>
                            <div class="info-value"><strong
                                    class="text-primary">{{ $rcData['rc_number'] ?? 'N/A' }}</strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Registration Date</div>
                            <div class="info-value">{{ $rcData['registration_date'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Registered At</div>
                            <div class="info-value">{{ $rcData['registered_at'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">RC Status</div>
                            <div class="info-value">{{ $rcData['rc_status'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Owner Number</div>
                            <div class="info-value">{{ $rcData['owner_number'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Masked Name</div>
                            <div class="info-value">
                                @if (isset($rcData['masked_name']) && $rcData['masked_name'])
                                    <span class="badge bg-warning">Yes</span>
                                @else
                                    <span class="badge bg-success">No</span>
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Latest By</div>
                            <div class="info-value">{{ $rcData['latest_by'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Less Info</div>
                            <div class="info-value">
                                @if (isset($rcData['less_info']) && $rcData['less_info'])
                                    <span class="badge bg-info">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </div>
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
                            <div class="info-value"><strong>{{ $rcData['owner_name'] ?? 'N/A' }}</strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Father Name</div>
                            <div class="info-value">{{ $rcData['father_name'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Mobile Number</div>
                            <div class="info-value">
                                {{ !empty($rcData['mobile_number']) ? $rcData['mobile_number'] : 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Present Address</div>
                            <div class="info-value">{{ $rcData['present_address'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Permanent Address</div>
                            <div class="info-value">{{ $rcData['permanent_address'] ?? 'N/A' }}</div>
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
                            <div class="info-value"><span
                                    class="badge bg-primary">{{ $rcData['vehicle_category'] ?? 'N/A' }}</span></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Vehicle Category Description</div>
                            <div class="info-value">{{ $rcData['vehicle_category_description'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Maker Description</div>
                            <div class="info-value">{{ $rcData['maker_description'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Maker Model</div>
                            <div class="info-value"><strong>{{ $rcData['maker_model'] ?? 'N/A' }}</strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Variant</div>
                            <div class="info-value">{{ $rcData['variant'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Body Type</div>
                            <div class="info-value">{{ $rcData['body_type'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Color</div>
                            <div class="info-value">{{ $rcData['color'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Chassis Number</div>
                            <div class="info-value"><code>{{ $rcData['vehicle_chasi_number'] ?? 'N/A' }}</code></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Engine Number</div>
                            <div class="info-value"><code>{{ $rcData['vehicle_engine_number'] ?? 'N/A' }}</code></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Fuel Type</div>
                            <div class="info-value">{{ $rcData['fuel_type'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Norms Type</div>
                            <div class="info-value">{{ $rcData['norms_type'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Manufacturing Date</div>
                            <div class="info-value">{{ $rcData['manufacturing_date'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Manufacturing Date Formatted</div>
                            <div class="info-value">{{ $rcData['manufacturing_date_formatted'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Vehicle Specifications --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Vehicle Specifications</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Cubic Capacity</div>
                            <div class="info-value">{{ $rcData['cubic_capacity'] ?? 'N/A' }} CC</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Number of Cylinders</div>
                            <div class="info-value">{{ $rcData['no_cylinders'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Vehicle Gross Weight</div>
                            <div class="info-value">{{ $rcData['vehicle_gross_weight'] ?? 'N/A' }} kg</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Unladen Weight</div>
                            <div class="info-value">{{ $rcData['unladen_weight'] ?? 'N/A' }} kg</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Wheelbase</div>
                            <div class="info-value">{{ $rcData['wheelbase'] ?? 'N/A' }} mm</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Seat Capacity</div>
                            <div class="info-value">{{ $rcData['seat_capacity'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Sleeper Capacity</div>
                            <div class="info-value">{{ $rcData['sleeper_capacity'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Standing Capacity</div>
                            <div class="info-value">{{ $rcData['standing_capacity'] ?? 'N/A' }}</div>
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
                            <div class="info-value">{{ $rcData['insurance_company'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Insurance Policy Number</div>
                            <div class="info-value"><code>{{ $rcData['insurance_policy_number'] ?? 'N/A' }}</code></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Insurance Valid Upto</div>
                            <div class="info-value">{{ $rcData['insurance_upto'] ?? 'N/A' }}</div>
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
                                @if (isset($rcData['financed']) && $rcData['financed'])
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Financer</div>
                            <div class="info-value">{{ $rcData['financer'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Permit Information --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Permit Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Permit Number</div>
                            <div class="info-value">{{ $rcData['permit_number'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Permit Type</div>
                            <div class="info-value">{{ $rcData['permit_type'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Permit Issue Date</div>
                            <div class="info-value">{{ $rcData['permit_issue_date'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Permit Valid From</div>
                            <div class="info-value">{{ $rcData['permit_valid_from'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Permit Valid Upto</div>
                            <div class="info-value">{{ $rcData['permit_valid_upto'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- National Permit --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-row">
                            <div class="info-label">National Permit Number</div>
                            <div class="info-value">{{ $rcData['national_permit_number'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-row">
                            <div class="info-label">National Permit Upto</div>
                            <div class="info-value">{{ $rcData['national_permit_upto'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-row">
                            <div class="info-label">National Permit Issued By</div>
                            <div class="info-value">{{ $rcData['national_permit_issued_by'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Tax & Fitness Information --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Tax & Fitness Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-row">
                            <div class="info-label">Tax Upto</div>
                            <div class="info-value">{{ $rcData['tax_upto'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-row">
                            <div class="info-label">Tax Paid Upto</div>
                            <div class="info-value">{{ $rcData['tax_paid_upto'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-row">
                            <div class="info-label">Fit Up To</div>
                            <div class="info-value">{{ $rcData['fit_up_to'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- PUCC Information --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">PUCC Number</div>
                            <div class="info-value">{{ !empty($rcData['pucc_number']) ? $rcData['pucc_number'] : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">PUCC Upto</div>
                            <div class="info-value">{{ $rcData['pucc_upto'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Additional Information --}}
                <div class="section-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Blacklist Status</div>
                            <div class="info-value">{{ $rcData['blacklist_status'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Non Use Status</div>
                            <div class="info-value">{{ $rcData['non_use_status'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Non Use From</div>
                            <div class="info-value">{{ $rcData['non_use_from'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Non Use To</div>
                            <div class="info-value">{{ $rcData['non_use_to'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">NOC Details</div>
                            <div class="info-value">{{ $rcData['noc_details'] ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Challan Details</div>
                            <div class="info-value">{{ $rcData['challan_details'] ?? 'N/A' }}</div>
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
