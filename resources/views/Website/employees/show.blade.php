@extends('Website.Layout.master')

@section('custom_css')
<style>
    .employee-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .profile-photo {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin-bottom: 20px;
    }

    .document-card {
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
    }

    .document-card:hover {
        border-color: #2196F3;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
    }

    .document-icon {
        font-size: 48px;
        color: #2196F3;
        margin-bottom: 10px;
    }

    .document-preview {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
        border: 2px solid #e0e0e0;
    }

    .section-header {
        color: #265b6b;
        font-weight: 600;
        border-bottom: 2px solid #265b6b;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .info-table th {
        width: 200px;
        color: #666;
        font-weight: 500;
    }

    .info-table td {
        font-weight: 600;
        color: #333;
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
    }

    .stats-card h4 {
        font-size: 32px;
        margin: 0;
    }

    .stats-card p {
        margin: 0;
        opacity: 0.9;
    }
</style>
@endsection

@section('content')
<div class="content-area">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #265b6b; font-weight: 700;">Employee Details - {{ $employee->emp_id }}</h2>
            <div>
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Edit
                </a>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            {{-- Left Column: Profile Info --}}
            <div class="col-md-4">
                <div class="card employee-card">
                    <div class="card-body text-center">
                        @if($employee->photo)
                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" class="profile-photo">
                        @else
                        <div class="profile-photo mx-auto d-flex align-items-center justify-content-center" style="background: #f0f0f0;">
                            <i class="fas fa-user fa-5x text-muted"></i>
                        </div>
                        @endif

                        <h3 class="mb-2">{{ $employee->name }}</h3>
                        <p class="text-muted mb-2">{{ $employee->designation }}</p>
                        <p class="text-muted mb-3"><i class="fas fa-building me-2"></i>{{ $employee->department }}</p>

                        <span class="badge bg-{{ $employee->status === 'active' ? 'success' : 'danger' }} mb-3" style="font-size: 14px;">
                            {{ ucfirst($employee->status) }}
                        </span>

                        <div class="mt-3">
                            <form action="{{ route('employees.toggle-status', $employee->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="btn btn-{{ $employee->status === 'active' ? 'danger' : 'success' }}"
                                        onclick="return confirm('Are you sure you want to change status?')">
                                    <i class="fas fa-toggle-{{ $employee->status === 'active' ? 'off' : 'on' }} me-2"></i>
                                    {{ $employee->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>

                        <hr class="my-3">

                        <div class="text-start">
                            <p class="mb-2"><i class="fas fa-envelope text-primary me-2"></i>{{ $employee->email }}</p>
                            <p class="mb-2"><i class="fas fa-phone text-success me-2"></i>{{ $employee->phone }}</p>
                            <p class="mb-2"><i class="fas fa-calendar text-info me-2"></i>Joined: {{ $employee->date_of_joining->format('d M, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Details & Documents --}}
            <div class="col-md-8">
                {{-- Personal Information --}}
                <div class="card employee-card mb-3">
                    <div class="card-body">
                        <h5 class="section-header">
                            <i class="fas fa-user me-2"></i>Personal Information
                        </h5>

                        <table class="table table-borderless info-table">
                            <tr>
                                <th><i class="fas fa-id-card me-2 text-primary"></i>Employee ID:</th>
                                <td><span class="badge bg-primary">{{ $employee->emp_id }}</span></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-user me-2 text-primary"></i>Full Name:</th>
                                <td>{{ $employee->name }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-envelope me-2 text-primary"></i>Email:</th>
                                <td>{{ $employee->email }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-phone me-2 text-primary"></i>Phone:</th>
                                <td>{{ $employee->phone }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-briefcase me-2 text-primary"></i>Designation:</th>
                                <td>{{ $employee->designation }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-building me-2 text-primary"></i>Department:</th>
                                <td>{{ $employee->department }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-calendar-check me-2 text-primary"></i>Date of Joining:</th>
                                <td>{{ $employee->date_of_joining->format('d M, Y') }} <span class="text-muted">({{ $employee->date_of_joining->diffForHumans() }})</span></td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-money-bill-wave me-2 text-primary"></i>Salary:</th>
                                <td>₹{{ number_format($employee->salary, 2) }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-map-marker-alt me-2 text-primary"></i>Address:</th>
                                <td>{{ $employee->address ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-clock me-2 text-primary"></i>Created:</th>
                                <td>{{ $employee->created_at->format('d M, Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <th><i class="fas fa-history me-2 text-primary"></i>Last Updated:</th>
                                <td>{{ $employee->updated_at->diffForHumans() }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Document Information --}}
                <div class="card employee-card">
                    <div class="card-body">
                        <h5 class="section-header">
                            <i class="fas fa-file-alt me-2"></i>Document Information
                        </h5>

                        <div class="row">
                            {{-- Aadhar Card Front --}}
                            <div class="col-md-6 mb-3">
                                <div class="document-card">
                                    @if($employee->aadhar_front)
                                        @if(Str::endsWith($employee->aadhar_front, '.pdf'))
                                        <i class="fas fa-file-pdf document-icon text-danger"></i>
                                        @else
                                        <img src="{{ asset('storage/' . $employee->aadhar_front) }}" alt="Aadhar Front" class="document-preview">
                                        @endif
                                        <h6 class="mb-2">Aadhar Card (Front)</h6>
                                        @if($employee->aadhar_number)
                                        <p class="text-muted mb-2">{{ $employee->aadhar_number }}</p>
                                        @endif
                                        <div class="btn-group">
                                            <a href="{{ route('employees.view-document', [$employee->id, 'aadhar_front']) }}" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="{{ route('employees.download-document', [$employee->id, 'aadhar_front']) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    @else
                                        <i class="fas fa-file-image document-icon text-muted"></i>
                                        <h6 class="mb-2">Aadhar Card (Front)</h6>
                                        <p class="text-muted">Not uploaded</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Aadhar Card Back --}}
                            <div class="col-md-6 mb-3">
                                <div class="document-card">
                                    @if($employee->aadhar_back)
                                        @if(Str::endsWith($employee->aadhar_back, '.pdf'))
                                        <i class="fas fa-file-pdf document-icon text-danger"></i>
                                        @else
                                        <img src="{{ asset('storage/' . $employee->aadhar_back) }}" alt="Aadhar Back" class="document-preview">
                                        @endif
                                        <h6 class="mb-2">Aadhar Card (Back)</h6>
                                        <div class="btn-group">
                                            <a href="{{ route('employees.view-document', [$employee->id, 'aadhar_back']) }}" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="{{ route('employees.download-document', [$employee->id, 'aadhar_back']) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    @else
                                        <i class="fas fa-file-image document-icon text-muted"></i>
                                        <h6 class="mb-2">Aadhar Card (Back)</h6>
                                        <p class="text-muted">Not uploaded</p>
                                    @endif
                                </div>
                            </div>

                            {{-- PAN Card --}}
                            <div class="col-md-6 mb-3">
                                <div class="document-card">
                                    @if($employee->pan_card)
                                        @if(Str::endsWith($employee->pan_card, '.pdf'))
                                        <i class="fas fa-file-pdf document-icon text-danger"></i>
                                        @else
                                        <img src="{{ asset('storage/' . $employee->pan_card) }}" alt="PAN Card" class="document-preview">
                                        @endif
                                        <h6 class="mb-2">PAN Card</h6>
                                        @if($employee->pan_number)
                                        <p class="text-muted mb-2">{{ $employee->pan_number }}</p>
                                        @endif
                                        <div class="btn-group">
                                            <a href="{{ route('employees.view-document', [$employee->id, 'pan_card']) }}" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="{{ route('employees.download-document', [$employee->id, 'pan_card']) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    @else
                                        <i class="fas fa-file-image document-icon text-muted"></i>
                                        <h6 class="mb-2">PAN Card</h6>
                                        <p class="text-muted">Not uploaded</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Driving License --}}
                            <div class="col-md-6 mb-3">
                                <div class="document-card">
                                    @if($employee->driving_license)
                                        @if(Str::endsWith($employee->driving_license, '.pdf'))
                                        <i class="fas fa-file-pdf document-icon text-danger"></i>
                                        @else
                                        <img src="{{ asset('storage/' . $employee->driving_license) }}" alt="Driving License" class="document-preview">
                                        @endif
                                        <h6 class="mb-2">Driving License</h6>
                                        @if($employee->dl_number)
                                        <p class="text-muted mb-2">{{ $employee->dl_number }}</p>
                                        @endif
                                        <div class="btn-group">
                                            <a href="{{ route('employees.view-document', [$employee->id, 'driving_license']) }}" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="{{ route('employees.download-document', [$employee->id, 'driving_license']) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    @else
                                        <i class="fas fa-file-image document-icon text-muted"></i>
                                        <h6 class="mb-2">Driving License</h6>
                                        <p class="text-muted">Not uploaded</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Address Proof --}}
                            <div class="col-md-6 mb-3">
                                <div class="document-card">
                                    @if($employee->address_proof)
                                        @if(Str::endsWith($employee->address_proof, '.pdf'))
                                        <i class="fas fa-file-pdf document-icon text-danger"></i>
                                        @else
                                        <img src="{{ asset('storage/' . $employee->address_proof) }}" alt="Address Proof" class="document-preview">
                                        @endif
                                        <h6 class="mb-2">Address Proof</h6>
                                        <div class="btn-group">
                                            <a href="{{ route('employees.view-document', [$employee->id, 'address_proof']) }}" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="{{ route('employees.download-document', [$employee->id, 'address_proof']) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    @else
                                        <i class="fas fa-file-image document-icon text-muted"></i>
                                        <h6 class="mb-2">Address Proof</h6>
                                        <p class="text-muted">Not uploaded</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('custom_js')
<script>
// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>
@endsection
