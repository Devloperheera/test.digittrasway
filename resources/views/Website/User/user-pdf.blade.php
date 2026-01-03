<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>User Detail - {{ $user->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #333; padding-bottom: 10px; }
        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section-title { background-color: #4CAF50; color: white; padding: 10px; font-size: 14px; font-weight: bold; margin-bottom: 15px; }
        .row { display: table; width: 100%; }
        .col { display: table-cell; padding: 5px 10px; vertical-align: top; }
        .label { font-weight: bold; color: #555; }
        .value { color: #333; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .badge { padding: 3px 8px; border-radius: 3px; color: white; font-weight: bold; }
        .badge-success { background-color: #28a745; }
        .badge-danger { background-color: #dc3545; }
        .badge-warning { background-color: #ffc107; color: black; }
    </style>
</head>
<body>
    <div class="header">
        <h1>User Full Details Report</h1>
        <p>User ID: {{ $user->id }} | Generated: {{ date('d-m-Y H:i:s') }}</p>
    </div>

    <!-- Personal Information -->
    <div class="section">
        <div class="section-title">PERSONAL INFORMATION</div>
        <table>
            <tr>
                <td width="30%"><span class="label">Name:</span></td>
                <td><span class="value">{{ $user->name ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Contact Number:</span></td>
                <td><span class="value">{{ $user->contact_number }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Email:</span></td>
                <td><span class="value">{{ $user->email ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Date of Birth:</span></td>
                <td><span class="value">{{ $user->dob ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Gender:</span></td>
                <td><span class="value">{{ ucfirst($user->gender ?? 'N/A') }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Emergency Contact:</span></td>
                <td><span class="value">{{ $user->emergency_contact ?? 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Aadhaar Information -->
    <div class="section">
        <div class="section-title">AADHAAR INFORMATION</div>
        <table>
            <tr>
                <td width="30%"><span class="label">Aadhaar Number:</span></td>
                <td><span class="value">{{ $user->aadhar_number ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Aadhaar Verified:</span></td>
                <td>
                    @if($user->aadhaar_verified)
                        <span class="badge badge-success">Verified</span>
                    @else
                        <span class="badge badge-warning">Not Verified</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><span class="label">Verification Date:</span></td>
                <td><span class="value">{{ $user->aadhaar_verification_date ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Digilocker Client ID:</span></td>
                <td><span class="value">{{ $user->aadhaar_digilocker_client_id ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Verified DOB:</span></td>
                <td><span class="value">{{ $user->verified_dob ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Verified Gender:</span></td>
                <td><span class="value">{{ $user->verified_gender ?? 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <!-- PAN Information -->
    <div class="section">
        <div class="section-title">PAN INFORMATION</div>
        <table>
            <tr>
                <td width="30%"><span class="label">PAN Number:</span></td>
                <td><span class="value">{{ $user->pan_number ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">PAN Verified:</span></td>
                <td>
                    @if($user->pan_verified)
                        <span class="badge badge-success">Verified</span>
                    @else
                        <span class="badge badge-warning">Not Verified</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><span class="label">RC Verified:</span></td>
                <td>
                    @if($user->rc_verified)
                        <span class="badge badge-success">Verified</span>
                    @else
                        <span class="badge badge-warning">Not Verified</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Address Information -->
    <div class="section">
        <div class="section-title">ADDRESS INFORMATION</div>
        <table>
            <tr>
                <td width="30%"><span class="label">Full Address:</span></td>
                <td><span class="value">{{ $user->full_address ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">City:</span></td>
                <td><span class="value">{{ $user->city ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">State:</span></td>
                <td><span class="value">{{ $user->state ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Pincode:</span></td>
                <td><span class="value">{{ $user->pincode ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Postal Code:</span></td>
                <td><span class="value">{{ $user->postal_code ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Country:</span></td>
                <td><span class="value">{{ $user->country ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Same Address:</span></td>
                <td><span class="value">{{ $user->same_address ? 'Yes' : 'No' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Verified Address:</span></td>
                <td><span class="value">{{ $user->verified_address ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Verified Pincode:</span></td>
                <td><span class="value">{{ $user->verified_pincode ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Verified State:</span></td>
                <td><span class="value">{{ $user->verified_state ?? 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Bank Information -->
    <div class="section">
        <div class="section-title">BANK INFORMATION</div>
        <table>
            <tr>
                <td width="30%"><span class="label">Bank Name:</span></td>
                <td><span class="value">{{ $user->bank_name ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Account Number:</span></td>
                <td><span class="value">
                    @if($user->account_number)
                        {{ '****' . substr($user->account_number, -4) }}
                    @else
                        N/A
                    @endif
                </span></td>
            </tr>
            <tr>
                <td><span class="label">IFSC Code:</span></td>
                <td><span class="value">{{ $user->ifsc ?? 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Account Status -->
    <div class="section">
        <div class="section-title">ACCOUNT STATUS</div>
        <table>
            <tr>
                <td width="30%"><span class="label">Is Verified:</span></td>
                <td>
                    @if($user->is_verified)
                        <span class="badge badge-success">Yes</span>
                    @else
                        <span class="badge badge-danger">No</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><span class="label">Is Completed:</span></td>
                <td>
                    @if($user->is_completed)
                        <span class="badge badge-success">Yes</span>
                    @else
                        <span class="badge badge-warning">No</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><span class="label">Declaration:</span></td>
                <td><span class="value">{{ $user->declaration ? 'Accepted' : 'Not Accepted' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Password Set:</span></td>
                <td><span class="value">{{ !empty($user->password) ? 'Yes' : 'No' }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Activity Logs -->
    <div class="section">
        <div class="section-title">ACTIVITY LOGS</div>
        <table>
            <tr>
                <td width="30%"><span class="label">Login Count:</span></td>
                <td><span class="value">{{ $user->login_count ?? 0 }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Last Login At:</span></td>
                <td><span class="value">{{ $user->last_login_at ? $user->last_login_at->format('d-m-Y H:i:s') : 'Never' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">OTP Attempts:</span></td>
                <td><span class="value">{{ $user->otp_attempts ?? 0 }}</span></td>
            </tr>
            <tr>
                <td><span class="label">OTP Resend Count:</span></td>
                <td><span class="value">{{ $user->otp_resend_count ?? 0 }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Created At:</span></td>
                <td><span class="value">{{ $user->created_at ? $user->created_at->format('d-m-Y H:i:s') : 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Updated At:</span></td>
                <td><span class="value">{{ $user->updated_at ? $user->updated_at->format('d-m-Y H:i:s') : 'N/A' }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Verification Completed At:</span></td>
                <td><span class="value">{{ $user->verification_completed_at ? $user->verification_completed_at->format('d-m-Y H:i:s') : 'N/A' }}</span></td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #666; border-top: 2px solid #333; padding-top: 15px;">
        <p><strong>DigiTransway System</strong> | User Report Generated on {{ date('d-m-Y H:i:s') }}</p>
        <p>This is a system-generated document.</p>
    </div>
</body>
</html>
