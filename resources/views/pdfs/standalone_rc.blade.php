<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>RC Details - {{ $data['rc_number'] ?? 'N/A' }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 11px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #4CAF50; padding-bottom: 15px; }
        .header h1 { color: #333; margin: 0; font-size: 22px; }
        .section-header { background-color: #11998e; color: white; padding: 10px 15px; margin-top: 20px; margin-bottom: 15px; border-radius: 5px; font-weight: bold; font-size: 13px; }
        .info-row { padding: 8px; border-bottom: 1px solid #ddd; display: flex; }
        .info-label { font-weight: bold; width: 40%; color: #555; }
        .info-value { width: 60%; color: #333; }
        .verified-badge { background-color: #4CAF50; color: white; padding: 5px 15px; border-radius: 5px; display: inline-block; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RC (Registration Certificate) Verification Details</h1>
        <p>Verified via Government Database - SurePass API</p>
    </div>

    {{-- Basic RC Information --}}
    <div class="section-header">Basic RC Information</div>
    <div class="info-row"><div class="info-label">Client ID:</div><div class="info-value">{{ $data['client_id'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">RC Number:</div><div class="info-value"><strong>{{ $data['rc_number'] ?? 'N/A' }}</strong></div></div>
    <div class="info-row"><div class="info-label">Registration Date:</div><div class="info-value">{{ $data['registration_date'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Registered At:</div><div class="info-value">{{ $data['registered_at'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Owner Number:</div><div class="info-value">{{ $data['owner_number'] ?? 'N/A' }}</div></div>

    {{-- Owner Information --}}
    <div class="section-header">Owner Information</div>
    <div class="info-row"><div class="info-label">Owner Name:</div><div class="info-value"><strong>{{ $data['owner_name'] ?? 'N/A' }}</strong></div></div>
    <div class="info-row"><div class="info-label">Father Name:</div><div class="info-value">{{ $data['father_name'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Mobile Number:</div><div class="info-value">{{ !empty($data['mobile_number']) ? $data['mobile_number'] : 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Present Address:</div><div class="info-value">{{ $data['present_address'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Permanent Address:</div><div class="info-value">{{ $data['permanent_address'] ?? 'N/A' }}</div></div>

    {{-- Vehicle Information --}}
    <div class="section-header">Vehicle Information</div>
    <div class="info-row"><div class="info-label">Vehicle Category:</div><div class="info-value">{{ $data['vehicle_category'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Vehicle Category Description:</div><div class="info-value">{{ $data['vehicle_category_description'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Maker Description:</div><div class="info-value">{{ $data['maker_description'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Maker Model:</div><div class="info-value"><strong>{{ $data['maker_model'] ?? 'N/A' }}</strong></div></div>
    <div class="info-row"><div class="info-label">Body Type:</div><div class="info-value">{{ $data['body_type'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Color:</div><div class="info-value">{{ $data['color'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Chassis Number:</div><div class="info-value">{{ $data['vehicle_chasi_number'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Engine Number:</div><div class="info-value">{{ $data['vehicle_engine_number'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Fuel Type:</div><div class="info-value">{{ $data['fuel_type'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Norms Type:</div><div class="info-value">{{ $data['norms_type'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Manufacturing Date:</div><div class="info-value">{{ $data['manufacturing_date'] ?? 'N/A' }}</div></div>

    {{-- Vehicle Specifications --}}
    <div class="section-header">Vehicle Specifications</div>
    <div class="info-row"><div class="info-label">Cubic Capacity:</div><div class="info-value">{{ $data['cubic_capacity'] ?? 'N/A' }} CC</div></div>
    <div class="info-row"><div class="info-label">Number of Cylinders:</div><div class="info-value">{{ $data['no_cylinders'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Vehicle Gross Weight:</div><div class="info-value">{{ $data['vehicle_gross_weight'] ?? 'N/A' }} kg</div></div>
    <div class="info-row"><div class="info-label">Unladen Weight:</div><div class="info-value">{{ $data['unladen_weight'] ?? 'N/A' }} kg</div></div>
    <div class="info-row"><div class="info-label">Wheelbase:</div><div class="info-value">{{ $data['wheelbase'] ?? 'N/A' }} mm</div></div>
    <div class="info-row"><div class="info-label">Seat Capacity:</div><div class="info-value">{{ $data['seat_capacity'] ?? 'N/A' }}</div></div>

    {{-- Insurance Information --}}
    <div class="section-header">Insurance Information</div>
    <div class="info-row"><div class="info-label">Insurance Company:</div><div class="info-value">{{ $data['insurance_company'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Insurance Policy Number:</div><div class="info-value">{{ $data['insurance_policy_number'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Insurance Valid Upto:</div><div class="info-value">{{ $data['insurance_upto'] ?? 'N/A' }}</div></div>

    {{-- Finance Information --}}
    <div class="section-header">Finance Information</div>
    <div class="info-row"><div class="info-label">Financed:</div><div class="info-value">{{ (isset($data['financed']) && $data['financed']) ? 'Yes' : 'No' }}</div></div>
    <div class="info-row"><div class="info-label">Financer:</div><div class="info-value">{{ $data['financer'] ?? 'N/A' }}</div></div>

    {{-- Permit Information --}}
    <div class="section-header">Permit Information</div>
    <div class="info-row"><div class="info-label">Permit Number:</div><div class="info-value">{{ $data['permit_number'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Permit Type:</div><div class="info-value">{{ $data['permit_type'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Permit Valid Upto:</div><div class="info-value">{{ $data['permit_valid_upto'] ?? 'N/A' }}</div></div>

    {{-- Tax & Fitness --}}
    <div class="section-header">Tax & Fitness Information</div>
    <div class="info-row"><div class="info-label">Tax Paid Upto:</div><div class="info-value">{{ $data['tax_paid_upto'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">Fit Up To:</div><div class="info-value">{{ $data['fit_up_to'] ?? 'N/A' }}</div></div>
    <div class="info-row"><div class="info-label">PUCC Number:</div><div class="info-value">{{ !empty($data['pucc_number']) ? $data['pucc_number'] : 'N/A' }}</div></div>

    {{-- Verification Status --}}
    <div class="section-header">Verification Status</div>
    <div class="info-row"><div class="info-label">Government Verified:</div><div class="info-value"><span class="verified-badge">✓ Verified via SurePass API</span></div></div>

    <div class="footer">
        <p>This is a computer-generated document. Generated on {{ date('d M Y, h:i A') }}</p>
        <p>© {{ date('Y') }} Your Company Name. All rights reserved.</p>
    </div>
</body>
</html>
