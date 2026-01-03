<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DL Details - {{ $vendor->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2196F3;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #333;
            margin: 0;
        }
        .section-header {
            background-color: #667eea;
            color: white;
            padding: 10px 15px;
            margin-top: 25px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .info-section {
            margin: 20px 0;
        }
        .info-row {
            padding: 10px;
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
            background-color: #2196F3;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }
        .vehicle-classes {
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            margin-top: 5px;
            color: #1976d2;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .profile-section {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 8px;
        }
        .profile-image {
            max-width: 150px;
            border: 3px solid #667eea;
            border-radius: 8px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Driving License Verification Details</h1>
        <p>Verified via Government Database - SurePass API</p>
    </div>

    {{-- Profile Image Section --}}
    @if(isset($dlData['has_image']) && $dlData['has_image'] && !empty($dlData['profile_image']))
    <div class="profile-section">
        <h3>License Holder Photo</h3>
        <img src="data:image/jpeg;base64,{{ $dlData['profile_image'] }}" class="profile-image" alt="Profile Photo">
    </div>
    @endif

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
    </div>

    {{-- Basic DL Information --}}
    <div class="info-section">
        <div class="section-header">Basic DL Information</div>
        <div class="info-row">
            <div class="info-label">Client ID:</div>
            <div class="info-value">{{ $dlData['client_id'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">License Number:</div>
            <div class="info-value"><strong>{{ $dlData['license_number'] ?? $dlData['dl_number'] ?? 'N/A' }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">State:</div>
            <div class="info-value">{{ $dlData['state'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">OLA Name:</div>
            <div class="info-value">{{ $dlData['ola_name'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">OLA Code:</div>
            <div class="info-value">{{ $dlData['ola_code'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Citizenship:</div>
            <div class="info-value">{{ !empty($dlData['citizenship']) ? $dlData['citizenship'] : 'N/A' }}</div>
        </div>
    </div>

    {{-- Personal Information --}}
    <div class="info-section">
        <div class="section-header">Personal Information</div>
        <div class="info-row">
            <div class="info-label">Full Name:</div>
            <div class="info-value"><strong>{{ $dlData['name'] ?? 'N/A' }}</strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Father/Husband Name:</div>
            <div class="info-value">{{ $dlData['father_or_husband_name'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Birth:</div>
            <div class="info-value">{{ $dlData['dob'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Gender:</div>
            <div class="info-value">
                @php
                    $gender = $dlData['gender'] ?? 'N/A';
                    if ($gender === 'M') $gender = 'Male';
                    elseif ($gender === 'F') $gender = 'Female';
                    elseif ($gender === 'O') $gender = 'Other';
                @endphp
                {{ $gender }}
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Blood Group:</div>
            <div class="info-value">{{ !empty($dlData['blood_group']) ? $dlData['blood_group'] : 'N/A' }}</div>
        </div>
    </div>

    {{-- Address Information --}}
    <div class="info-section">
        <div class="section-header">Address Information</div>
        <div class="info-row">
            <div class="info-label">Permanent Address:</div>
            <div class="info-value">{{ $dlData['permanent_address'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Permanent ZIP:</div>
            <div class="info-value">{{ !empty($dlData['permanent_zip']) ? $dlData['permanent_zip'] : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Temporary Address:</div>
            <div class="info-value">{{ $dlData['temporary_address'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Temporary ZIP:</div>
            <div class="info-value">{{ !empty($dlData['temporary_zip']) ? $dlData['temporary_zip'] : 'N/A' }}</div>
        </div>
    </div>

    {{-- License Validity --}}
    <div class="info-section">
        <div class="section-header">License Validity Information</div>
        <div class="info-row">
            <div class="info-label">Date of Issue (Non-Transport):</div>
            <div class="info-value">{{ $dlData['doi'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Expiry (Non-Transport):</div>
            <div class="info-value">{{ $dlData['doe'] ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Issue (Transport):</div>
            <div class="info-value">
                @if(isset($dlData['transport_doi']) && $dlData['transport_doi'] !== '1800-01-01')
                    {{ $dlData['transport_doi'] }}
                @else
                    N/A
                @endif
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Date of Expiry (Transport):</div>
            <div class="info-value">
                @if(isset($dlData['transport_doe']) && $dlData['transport_doe'] !== '1800-01-01')
                    {{ $dlData['transport_doe'] }}
                @else
                    N/A
                @endif
            </div>
        </div>
    </div>

    {{-- Vehicle Classes --}}
    <div class="info-section">
        <div class="section-header">Authorized Vehicle Classes</div>
        <div class="info-row">
            <div class="info-label">Vehicle Classes:</div>
            <div class="info-value">
                @if(isset($dlData['vehicle_classes']) && is_array($dlData['vehicle_classes']) && count($dlData['vehicle_classes']) > 0)
                    <div class="vehicle-classes">
                        {{ implode(', ', $dlData['vehicle_classes']) }}
                    </div>
                @else
                    N/A
                @endif
            </div>
        </div>
    </div>

    {{-- Verification Status --}}
    <div class="info-section">
        <div class="section-header">Verification Status</div>
        <div class="info-row">
            <div class="info-label">Has Profile Image:</div>
            <div class="info-value">{{ (isset($dlData['has_image']) && $dlData['has_image']) ? 'Yes' : 'No' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Less Info:</div>
            <div class="info-value">{{ (isset($dlData['less_info']) && $dlData['less_info']) ? 'Yes' : 'No' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Verified At:</div>
            <div class="info-value">{{ $dlData['verified_at'] ?? now()->format('d M Y, h:i A') }}</div>
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
