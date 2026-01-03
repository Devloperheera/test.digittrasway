<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print User Detail - {{ $user->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            body { font-size: 12px; }
        }
        body { padding: 20px; }
        .section-title { background-color: #007bff; color: white; padding: 10px; margin-top: 20px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="text-center no-print mb-3">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print This Page
        </button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="container">
        <div class="text-center mb-4">
            <h2>User Full Details Report</h2>
            <p>User ID: {{ $user->id }} | Generated: {{ date('d-m-Y H:i:s') }}</p>
        </div>

        <!-- Personal Information -->
        <h5 class="section-title">PERSONAL INFORMATION</h5>
        <table class="table table-bordered">
            <tr>
                <td width="30%"><strong>Name:</strong></td>
                <td>{{ $user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Contact Number:</strong></td>
                <td>{{ $user->contact_number }}</td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>{{ $user->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Date of Birth:</strong></td>
                <td>{{ $user->dob ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Gender:</strong></td>
                <td>{{ ucfirst($user->gender ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td><strong>Emergency Contact:</strong></td>
                <td>{{ $user->emergency_contact ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- Aadhaar Information -->
        <h5 class="section-title">AADHAAR INFORMATION</h5>
        <table class="table table-bordered">
            <tr>
                <td width="30%"><strong>Aadhaar Number:</strong></td>
                <td>{{ $user->aadhar_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Aadhaar Verified:</strong></td>
                <td>{{ $user->aadhaar_verified ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td><strong>Verification Date:</strong></td>
                <td>{{ $user->aadhaar_verification_date ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Digilocker Client ID:</strong></td>
                <td>{{ $user->aadhaar_digilocker_client_id ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- PAN Information -->
        <h5 class="section-title">PAN INFORMATION</h5>
        <table class="table table-bordered">
            <tr>
                <td width="30%"><strong>PAN Number:</strong></td>
                <td>{{ $user->pan_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>PAN Verified:</strong></td>
                <td>{{ $user->pan_verified ? 'Yes' : 'No' }}</td>
            </tr>
        </table>

        <!-- Address Information -->
        <h5 class="section-title">ADDRESS INFORMATION</h5>
        <table class="table table-bordered">
            <tr>
                <td width="30%"><strong>Full Address:</strong></td>
                <td>{{ $user->full_address ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>City:</strong></td>
                <td>{{ $user->city ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>State:</strong></td>
                <td>{{ $user->state ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Pincode:</strong></td>
                <td>{{ $user->pincode ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Country:</strong></td>
                <td>{{ $user->country ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- Bank Information -->
        <h5 class="section-title">BANK INFORMATION</h5>
        <table class="table table-bordered">
            <tr>
                <td width="30%"><strong>Bank Name:</strong></td>
                <td>{{ $user->bank_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Account Number:</strong></td>
                <td>
                    @if($user->account_number)
                        {{ '****' . substr($user->account_number, -4) }}
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>IFSC Code:</strong></td>
                <td>{{ $user->ifsc ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="text-center mt-4" style="border-top: 2px solid #333; padding-top: 15px;">
            <p><strong>DigiTransway System</strong> | Generated on {{ date('d-m-Y H:i:s') }}</p>
        </div>
    </div>

    <script>
        // Auto print on page load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
