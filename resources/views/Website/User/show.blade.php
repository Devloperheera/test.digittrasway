@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .card-header {
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }

        .card-body p {
            margin-bottom: 10px;
            line-height: 1.8;
        }

        .badge {
            padding: 5px 10px;
            font-size: 12px;
        }

        .img-thumbnail {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 5px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .img-thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
        }

        .btn-group {
            gap: 5px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        pre {
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="form-section" class="form-section">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-id-card-alt me-2"></i>
                        User Aadhaar Full Details
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('users.index') }}">
                                    <i class="fas fa-users"></i> Users
                                </a>
                            </li>
                            <li class="breadcrumb-item active">User Details - {{ $user->name ?? 'User #' . $user->id }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <a href="{{ route('users.export.user.excel', $user->id) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                        <a href="{{ route('users.export.user.csv', $user->id) }}" class="btn btn-info">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                        <a href="{{ route('users.export.user.pdf', $user->id) }}" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                        <a href="{{ route('users.print', $user->id) }}" target="_blank" class="btn btn-secondary">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i> Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>ID:</strong> {{ $user->id }}</p>
                            <p><strong>Name:</strong> {{ $user->name ?? 'N/A' }}</p>
                            <p><strong>Contact Number:</strong> {{ $user->contact_number }}</p>
                            <p><strong>Email:</strong> {{ $user->email ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Date of Birth:</strong> {{ $user->dob ?? 'N/A' }}</p>
                            <p><strong>Gender:</strong> {{ ucfirst($user->gender ?? 'N/A') }}</p>
                            <p><strong>Emergency Contact:</strong> {{ $user->emergency_contact ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Verified:</strong>
                                @if ($user->is_verified)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-danger">No</span>
                                @endif
                            </p>
                            <p><strong>Registration Completed:</strong>
                                @if ($user->is_completed)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-warning text-dark">No</span>
                                @endif
                            </p>
                            <p><strong>Password Set:</strong>
                                @if (!empty($user->password))
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aadhaar Information -->
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i> Aadhaar Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Aadhaar Number:</strong> {{ $user->aadhar_number ?? 'N/A' }}</p>
                            <p><strong>Aadhaar Verified:</strong>
                                @if ($user->aadhaar_verified)
                                    <span class="badge bg-success">Verified</span>
                                @else
                                    <span class="badge bg-warning text-dark">Not Verified</span>
                                @endif
                            </p>
                            <p><strong>Verification Date:</strong> {{ $user->aadhaar_verification_date ?? 'N/A' }}</p>
                            <p><strong>Digilocker Client ID:</strong> {{ $user->aadhaar_digilocker_client_id ?? 'N/A' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            @if ($user->aadhaar_verified_data)
                                <p><strong>Verified Data:</strong></p>
                                <pre class="bg-light p-3 rounded">{{ json_encode(json_decode($user->aadhaar_verified_data), JSON_PRETTY_PRINT) }}</pre>
                            @endif

                            @if ($user->verified_dob || $user->verified_gender)
                                <p><strong>Verified DOB:</strong> {{ $user->verified_dob ?? 'N/A' }}</p>
                                <p><strong>Verified Gender:</strong> {{ $user->verified_gender ?? 'N/A' }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6><i class="fas fa-image me-1"></i> Aadhaar Front Image:</h6>
                            @if ($user->aadhar_front)
                                <img src="{{ asset('storage/' . $user->aadhar_front) }}" alt="Aadhaar Front"
                                    class="img-thumbnail" style="max-width: 100%; max-height: 300px;"
                                    onclick="openImageModal(this.src)">
                            @else
                                <p class="text-muted"><i class="fas fa-times-circle"></i> No image uploaded</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-image me-1"></i> Aadhaar Back Image:</h6>
                            @if ($user->aadhar_back)
                                <img src="{{ asset('storage/' . $user->aadhar_back) }}" alt="Aadhaar Back"
                                    class="img-thumbnail" style="max-width: 100%; max-height: 300px;"
                                    onclick="openImageModal(this.src)">
                            @else
                                <p class="text-muted"><i class="fas fa-times-circle"></i> No image uploaded</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAN Information -->
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i> PAN Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>PAN Number:</strong> {{ $user->pan_number ?? 'N/A' }}</p>
                            <p><strong>PAN Verified:</strong>
                                @if ($user->pan_verified)
                                    <span class="badge bg-success">Verified</span>
                                @else
                                    <span class="badge bg-warning text-dark">Not Verified</span>
                                @endif
                            </p>
                            <p><strong>RC Verified:</strong>
                                @if ($user->rc_verified)
                                    <span class="badge bg-success">Verified</span>
                                @else
                                    <span class="badge bg-warning text-dark">Not Verified</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-image me-1"></i> PAN Card Image:</h6>
                            @if ($user->pan_image)
                                <img src="{{ asset('storage/' . $user->pan_image) }}" alt="PAN Card" class="img-thumbnail"
                                    style="max-width: 100%; max-height: 300px;" onclick="openImageModal(this.src)">
                            @else
                                <p class="text-muted"><i class="fas fa-times-circle"></i> No image uploaded</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i> Address Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Full Address:</strong> {{ $user->full_address ?? 'N/A' }}</p>
                            <p><strong>City:</strong> {{ $user->city ?? 'N/A' }}</p>
                            <p><strong>State:</strong> {{ $user->state ?? 'N/A' }}</p>
                            <p><strong>Pincode:</strong> {{ $user->pincode ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Postal Code:</strong> {{ $user->postal_code ?? 'N/A' }}</p>
                            <p><strong>Country:</strong> {{ $user->country ?? 'N/A' }}</p>
                            <p><strong>Same Address:</strong>
                                {{ $user->same_address ? 'Yes' : 'No' }}
                            </p>
                            <p><strong>Declaration:</strong>
                                {{ $user->declaration ? 'Accepted' : 'Not Accepted' }}
                            </p>
                        </div>
                    </div>

                    @if ($user->verified_address || $user->verified_pincode || $user->verified_state)
                        <hr>
                        <h6><i class="fas fa-check-circle me-1"></i> Verified Address Details:</h6>
                        <div class="row">
                            <div class="col-md-12">
                                <p><strong>Verified Address:</strong> {{ $user->verified_address ?? 'N/A' }}</p>
                                <p><strong>Verified Pincode:</strong> {{ $user->verified_pincode ?? 'N/A' }}</p>
                                <p><strong>Verified State:</strong> {{ $user->verified_state ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bank Information -->
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-university me-2"></i> Bank Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Bank Name:</strong> {{ $user->bank_name ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Account Number:</strong>
                                @if ($user->account_number)
                                    {{ '****' . substr($user->account_number, -4) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>IFSC Code:</strong> {{ $user->ifsc ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OTP & Activity Information -->
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> OTP & Activity Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-mobile-alt me-1"></i> OTP Details:</h6>
                            <p><strong>OTP Attempts:</strong> {{ $user->otp_attempts ?? 0 }}</p>
                            <p><strong>OTP Resend Count:</strong> {{ $user->otp_resend_count ?? 0 }}</p>
                            <p><strong>Last OTP Sent At:</strong>
                                {{ $user->last_otp_sent_at ? $user->last_otp_sent_at->format('d-m-Y H:i:s') : 'N/A' }}
                            </p>
                            <p><strong>OTP Expires At:</strong>
                                {{ $user->otp_expires_at ? $user->otp_expires_at->format('d-m-Y H:i:s') : 'N/A' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-history me-1"></i> Activity Logs:</h6>
                            <p><strong>Login Count:</strong> {{ $user->login_count ?? 0 }}</p>
                            <p><strong>Last Login At:</strong>
                                {{ $user->last_login_at ? $user->last_login_at->format('d-m-Y H:i:s') : 'Never' }}
                            </p>
                            <p><strong>Account Created:</strong>
                                {{ $user->created_at ? $user->created_at->format('d-m-Y H:i:s') : 'N/A' }}
                            </p>
                            <p><strong>Last Updated:</strong>
                                {{ $user->updated_at ? $user->updated_at->format('d-m-Y H:i:s') : 'N/A' }}
                            </p>
                            <p><strong>Verification Completed At:</strong>
                                {{ $user->verification_completed_at ? $user->verification_completed_at->format('d-m-Y H:i:s') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mb-4">
                <a href="{{ route('users.index') }}" class="btn btn-lg btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users List
                </a>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Document Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Document" style="max-width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        // Image Modal Function
        function openImageModal(imageSrc) {
            $('#modalImage').attr('src', imageSrc);
            $('#imageModal').modal('show');
        }

        $(document).ready(function() {
            // Smooth scroll to top
            $('html, body').animate({
                scrollTop: 0
            }, 'fast');

            // Add click to copy functionality for IDs
            $('p:contains("ID:")').on('click', function() {
                const idText = $(this).text().replace('ID: ', '');
                navigator.clipboard.writeText(idText).then(function() {
                    toastr.success('ID copied to clipboard!');
                }).catch(function() {
                    console.log('Copy failed');
                });
            });

            // Highlight verified sections
            $('.badge.bg-success').closest('.card').addClass('border-success');
        });
    </script>
@endsection
