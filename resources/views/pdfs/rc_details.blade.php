<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>RC Details - {{ $vendor->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            font-size: 11px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 22px;
        }
        .section-header {
            background-color: #11998e;
            color: white;
            padding: 10px 15px;
            margin-top: 25px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 13px;
        }
        .info-section {
            margin: 20px 0;
        }
        .info-row {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            display: flex;
        }
        .info-label {
            font-weight: bold;
            width: 40%;
            color: #555;
        }
        .info-value {
            width: 60%;
            color: #333;
        }
        .verified-badge {
            background-color: #4CAF50;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            display: inline-block;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .two-column {
            display: flex;
            gap: 20px;
        }
        .column {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RC (Registration Certificate) Verification Details</h1>
        <p>Verified via Government Database - SurePass API</p>
    </div>

    {{-- Vendor Information --}}
    <div class="info-section">
        <div class="section-header">Vendor Information</div>
        <div class="info-row">
            <div class="info-label">Vendor Name:</div>
            <div class="info-value">{{ $vendor->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Contact Number:</div>
            <div class="info-value">{{ $vendor->contact_number }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div class="info-value">{{ $vendor->email ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Basic RC Information --}}
    <div class="info-section">
        <div class="section-header">Basic RC Information</div>
        <div class="info-row">
            <div class="info-label">Client ID:</div>
            <div class="info-value">{{ $rcData['client_id'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">RC Number:</div>
            <div class="info-value"><strong>{{ $rcData['rc_number'] ?? 'N/A' }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Registration Date:</div>
            <div class="info-value">{{ $rcData['registration_date'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Registered At:</div>
            <div class="info-value">{{ $rcData['registered_at'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">RC Status:</div>
            <div class="info-value">{{ $rcData['rc_status'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Owner Number:</div>
            <div class="info-value">{{ $rcData['owner_number'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Owner Information --}}
    <div class="info-section">
        <div class="section-header">Owner Information</div>
        <div class="info-row">
            <div class="info-label">Owner Name:</div>
            <div class="info-value"><strong>{{ $rcData['owner_name'] ?? 'N/A' }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Father Name:</div>
            <div class="info-value">{{ $rcData['father_name'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Mobile Number:</div>
            <div class="info-value">{{ !empty($rcData['mobile_number']) ? $rcData['mobile_number'] : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Present Address:</div>
            <div class="info-value">{{ $rcData['present_address'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Permanent Address:</div>
            <div class="info-value">{{ $rcData['permanent_address'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Vehicle Information --}}
    <div class="info-section">
        <div class="section-header">Vehicle Information</div>
        <div class="info-row">
            <div class="info-label">Vehicle Category:</div>
            <div class="info-value">{{ $rcData['vehicle_category'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Vehicle Category Description:</div>
            <div class="info-value">{{ $rcData['vehicle_category_description'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Maker Description:</div>
            <div class="info-value">{{ $rcData['maker_description'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Maker Model:</div>
            <div class="info-value"><strong>{{ $rcData['maker_model'] ?? 'N/A' }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Variant:</div>
            <div class="info-value">{{ $rcData['variant'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Body Type:</div>
            <div class="info-value">{{ $rcData['body_type'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Color:</div>
            <div class="info-value">{{ $rcData['color'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Chassis Number:</div>
            <div class="info-value">{{ $rcData['vehicle_chasi_number'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Engine Number:</div>
            <div class="info-value">{{ $rcData['vehicle_engine_number'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fuel Type:</div>
            <div class="info-value">{{ $rcData['fuel_type'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Norms Type:</div>
            <div class="info-value">{{ $rcData['norms_type'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Manufacturing Date:</div>
            <div class="info-value">{{ $rcData['manufacturing_date'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Manufacturing Date Formatted:</div>
            <div class="info-value">{{ $rcData['manufacturing_date_formatted'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Vehicle Specifications --}}
    <div class="info-section">
        <div class="section-header">Vehicle Specifications</div>
        <div class="info-row">
            <div class="info-label">Cubic Capacity:</div>
            <div class="info-value">{{ $rcData['cubic_capacity'] ?? 'N/A' }} CC</div>
        </div>
        <div class="info-row">
            <div class="info-label">Number of Cylinders:</div>
            <div class="info-value">{{ $rcData['no_cylinders'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Vehicle Gross Weight:</div>
            <div class="info-value">{{ $rcData['vehicle_gross_weight'] ?? 'N/A' }} kg</div>
        </div>
        <div class="info-row">
            <div class="info-label">Unladen Weight:</div>
            <div class="info-value">{{ $rcData['unladen_weight'] ?? 'N/A' }} kg</div>
        </div>
        <div class="info-row">
            <div class="info-label">Wheelbase:</div>
            <div class="info-value">{{ $rcData['wheelbase'] ?? 'N/A' }} mm</div>
        </div>
        <div class="info-row">
            <div class="info-label">Seat Capacity:</div>
            <div class="info-value">{{ $rcData['seat_capacity'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Sleeper Capacity:</div>
            <div class="info-value">{{ $rcData['sleeper_capacity'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Standing Capacity:</div>
            <div class="info-value">{{ $rcData['standing_capacity'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Insurance Information --}}
    <div class="info-section">
        <div class="section-header">Insurance Information</div>
        <div class="info-row">
            <div class="info-label">Insurance Company:</div>
            <div class="info-value">{{ $rcData['insurance_company'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Insurance Policy Number:</div>
            <div class="info-value">{{ $rcData['insurance_policy_number'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Insurance Valid Upto:</div>
            <div class="info-value">{{ $rcData['insurance_upto'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Finance Information --}}
    <div class="info-section">
        <div class="section-header">Finance Information</div>
        <div class="info-row">
            <div class="info-label">Financed:</div>
            <div class="info-value">{{ (isset($rcData['financed']) && $rcData['financed']) ? 'Yes' : 'No' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Financer:</div>
            <div class="info-value">{{ $rcData['financer'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Permit Information --}}
    <div class="info-section">
        <div class="section-header">Permit Information</div>
        <div class="info-row">
            <div class="info-label">Permit Number:</div>
            <div class="info-value">{{ $rcData['permit_number'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Permit Type:</div>
            <div class="info-value">{{ $rcData['permit_type'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Permit Issue Date:</div>
            <div class="info-value">{{ $rcData['permit_issue_date'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Permit Valid From:</div>
            <div class="info-value">{{ $rcData['permit_valid_from'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Permit Valid Upto:</div>
            <div class="info-value">{{ $rcData['permit_valid_upto'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">National Permit Number:</div>
            <div class="info-value">{{ $rcData['national_permit_number'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">National Permit Upto:</div>
            <div class="info-value">{{ $rcData['national_permit_upto'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">National Permit Issued By:</div>
            <div class="info-value">{{ $rcData['national_permit_issued_by'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Tax & Fitness Information --}}
    <div class="info-section">
        <div class="section-header">Tax & Fitness Information</div>
        <div class="info-row">
            <div class="info-label">Tax Upto:</div>
            <div class="info-value">{{ $rcData['tax_upto'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tax Paid Upto:</div>
            <div class="info-value">{{ $rcData['tax_paid_upto'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fit Up To:</div>
            <div class="info-value">{{ $rcData['fit_up_to'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">PUCC Number:</div>
            <div class="info-value">{{ !empty($rcData['pucc_number']) ? $rcData['pucc_number'] : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">PUCC Upto:</div>
            <div class="info-value">{{ $rcData['pucc_upto'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Additional Information --}}
    <div class="info-section">
        <div class="section-header">Additional Information</div>
        <div class="info-row">
            <div class="info-label">Blacklist Status:</div>
            <div class="info-value">{{ $rcData['blacklist_status'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Non Use Status:</div>
            <div class="info-value">{{ $rcData['non_use_status'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Non Use From:</div>
            <div class="info-value">{{ $rcData['non_use_from'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Non Use To:</div>
            <div class="info-value">{{ $rcData['non_use_to'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">NOC Details:</div>
            <div class="info-value">{{ $rcData['noc_details'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Challan Details:</div>
            <div class="info-value">{{ $rcData['challan_details'] ?? 'N/A' }}</div>
        </div>
    </div>

    {{-- Verification Status --}}
    <div class="info-section">
        <div class="section-header">Verification Status</div>
        <div class="info-row">
            <div class="info-label">Masked Name:</div>
            <div class="info-value">{{ (isset($rcData['masked_name']) && $rcData['masked_name']) ? 'Yes' : 'No' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Less Info:</div>
            <div class="info-value">{{ (isset($rcData['less_info']) && $rcData['less_info']) ? 'Yes' : 'No' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Latest By:</div>
            <div class="info-value">{{ $rcData['latest_by'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Verified At:</div>
            <div class="info-value">{{ $rcData['verified_at'] ?? now()->format('d M Y, h:i A') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Verification Method:</div>
            <div class="info-value">{{ strtoupper($rcData['verification_method'] ?? 'SUREPASS_API') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Government Verified:</div>
            <div class="info-value">
                <span class="verified-badge">✓ Verified via SurePass API</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This is a computer-generated document. Generated on {{ date('d M Y, h:i A') }}</p>
        <p>© {{ date('Y') }} Your Company Name. All rights reserved.</p>
    </div>
</body>
</html>
