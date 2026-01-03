@extends('Website.Layout.master')

@section('custom_css')
<style>
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .export-btns {
        display: flex;
        gap: 10px;
    }

    .section-title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
    }

    /* Employee Link Styling */
    .emp-link {
        color: #0d6efd;
        text-decoration: none;
        font-weight: 600;
        cursor: pointer;
    }

    .emp-link:hover {
        text-decoration: underline;
        color: #0a58ca;
    }

    /* ✅ NEW: Vendor ID Badge Style */
    .vendor-id-badge {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        display: inline-block;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Document Images Thumbnails */
    .doc-thumbnails {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        align-items: center;
    }

    .doc-thumb {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 5px;
        cursor: pointer;
        border: 2px solid #ddd;
        transition: all 0.2s;
    }

    .doc-thumb:hover {
        transform: scale(1.1);
        border-color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }

    .doc-badge {
        font-size: 9px;
        padding: 2px 5px;
        border-radius: 3px;
        display: block;
        text-align: center;
        margin-top: 2px;
        white-space: nowrap;
    }

    .no-docs {
        color: #999;
        font-size: 12px;
        font-style: italic;
    }

    /* Image Modal */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.95);
        animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .image-modal-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.5);
    }

    .close-modal {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #fff;
        font-size: 50px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10000;
        transition: color 0.3s;
    }

    .close-modal:hover {
        color: #f44336;
    }

    .image-caption {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 16px;
        max-width: 90%;
        text-align: center;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-users me-2"></i>
                Vendors Management
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <div class="export-btns">
                <button class="btn btn-success" onclick="exportData('excel')">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button class="btn btn-info" onclick="exportData('csv')">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button class="btn btn-danger" onclick="exportData('pdf')">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('vendors.index') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Vendor ID, Name, Contact, Email, RC, DL..."
                               value="{{ request('search') }}">
                    </div>

                    {{-- Employee Filter --}}
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-user-tie"></i> Employee</label>
                        <select name="employee_id" class="form-select">
                            <option value="">All Employees</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->emp_id }} - {{ $emp->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">RC Verified</label>
                        <select name="rc_verified" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('rc_verified') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ request('rc_verified') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">DL Verified</label>
                        <select name="dl_verified" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('dl_verified') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ request('dl_verified') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2 w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('vendors.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Vendor ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Emp ID</th>
                            <th>Employee Name</th>
                            <th>Documents</th>
                            <th>RC Details</th>
                            <th>DL Details</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                        <tr>
                            <td>#{{ $vendor->id }}</td>

                            {{-- ✅ VENDOR ID COLUMN --}}
                            <td>
                                <span class="vendor-id-badge">
                                    {{ $vendor->vendor_id ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <strong>{{ $vendor->name }}</strong><br>
                                <small class="text-muted">{{ $vendor->email ?? 'N/A' }}</small>
                            </td>
                            <td>
                                {{ $vendor->contact_number }}<br>
                                <small class="text-muted">{{ $vendor->city ?? 'N/A' }}</small>
                            </td>

                            {{-- Employee ID (Clickable) --}}
                            <td>
                                @if($vendor->referredByEmployee)
                                    <a href="{{ route('employees.show', $vendor->referredByEmployee->id) }}"
                                       class="emp-link"
                                       title="View Employee Details">
                                        {{ $vendor->referredByEmployee->emp_id }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Employee Name --}}
                            <td>
                                @if($vendor->referredByEmployee)
                                    {{ $vendor->referredByEmployee->name }}
                                @else
                                    <span class="text-muted">No Referral</span>
                                @endif
                            </td>

                            <td>
                                <div class="doc-thumbnails">
                                    @php
                                        $hasDocuments = false;
                                    @endphp

                                    {{-- Aadhaar Front --}}
                                    @if($vendor->aadhar_front)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->aadhar_front) }}"
                                                 class="doc-thumb"
                                                 alt="Aadhaar Front"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->aadhar_front) }}', 'Aadhaar Front - {{ $vendor->name }}')"
                                                 title="Aadhaar Front">
                                            <span class="doc-badge bg-primary text-white">Aadhar F</span>
                                        </div>
                                    @endif

                                    {{-- Aadhaar Back --}}
                                    @if($vendor->aadhar_back)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->aadhar_back) }}"
                                                 class="doc-thumb"
                                                 alt="Aadhaar Back"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->aadhar_back) }}', 'Aadhaar Back - {{ $vendor->name }}')"
                                                 title="Aadhaar Back">
                                            <span class="doc-badge bg-primary text-white">Aadhar B</span>
                                        </div>
                                    @endif

                                    {{-- PAN Card --}}
                                    @if($vendor->pan_image)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->pan_image) }}"
                                                 class="doc-thumb"
                                                 alt="PAN Card"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->pan_image) }}', 'PAN Card - {{ $vendor->name }}')"
                                                 title="PAN Card">
                                            <span class="doc-badge bg-success text-white">PAN</span>
                                        </div>
                                    @endif

                                    {{-- RC Document --}}
                                    @if($vendor->rc_image)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->rc_image) }}"
                                                 class="doc-thumb"
                                                 alt="RC Document"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->rc_image) }}', 'RC Document - {{ $vendor->name }}')"
                                                 title="RC Document">
                                            <span class="doc-badge bg-warning text-dark">RC</span>
                                        </div>
                                    @endif

                                    {{-- DL Document --}}
                                    @if($vendor->dl_image)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->dl_image) }}"
                                                 class="doc-thumb"
                                                 alt="DL Document"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->dl_image) }}', 'Driving License - {{ $vendor->name }}')"
                                                 title="Driving License">
                                            <span class="doc-badge bg-info text-white">DL</span>
                                        </div>
                                    @endif

                                    {{-- Vehicle Image --}}
                                    @if($vendor->vehicle_image)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->vehicle_image) }}"
                                                 class="doc-thumb"
                                                 alt="Vehicle"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->vehicle_image) }}', 'Vehicle - {{ $vendor->name }}')"
                                                 title="Vehicle Image">
                                            <span class="doc-badge bg-secondary text-white">Vehicle</span>
                                        </div>
                                    @endif

                                    {{-- Vehicle RC --}}
                                    @if($vendor->vehicle_rc_document)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->vehicle_rc_document) }}"
                                                 class="doc-thumb"
                                                 alt="Vehicle RC"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->vehicle_rc_document) }}', 'Vehicle RC - {{ $vendor->name }}')"
                                                 title="Vehicle RC">
                                            <span class="doc-badge bg-danger text-white">V-RC</span>
                                        </div>
                                    @endif

                                    {{-- Vehicle Insurance --}}
                                    @if($vendor->vehicle_insurance_document)
                                        @php $hasDocuments = true; @endphp
                                        <div>
                                            <img src="{{ asset('storage/' . $vendor->vehicle_insurance_document) }}"
                                                 class="doc-thumb"
                                                 alt="Insurance"
                                                 onclick="showImage('{{ asset('storage/' . $vendor->vehicle_insurance_document) }}', 'Vehicle Insurance - {{ $vendor->name }}')"
                                                 title="Vehicle Insurance">
                                            <span class="doc-badge bg-dark text-white">Insurance</span>
                                        </div>
                                    @endif

                                    @if(!$hasDocuments)
                                        <span class="no-docs">No documents uploaded</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($vendor->rc_verified && $vendor->rc_verified_data)
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-check-circle"></i> Verified
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.rc-details', $vendor->id) }}">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.rc-export.excel', $vendor->id) }}">
                                                    <i class="fas fa-file-excel"></i> Export Excel
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.rc-export.csv', $vendor->id) }}">
                                                    <i class="fas fa-file-csv"></i> Export CSV
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.rc-export.pdf', $vendor->id) }}">
                                                    <i class="fas fa-file-pdf"></i> Export PDF
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($vendor->dl_verified && $vendor->dl_verified_data)
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-check-circle"></i> Verified
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.dl-details', $vendor->id) }}">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.dl-export.excel', $vendor->id) }}">
                                                    <i class="fas fa-file-excel"></i> Export Excel
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.dl-export.csv', $vendor->id) }}">
                                                    <i class="fas fa-file-csv"></i> Export CSV
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('vendors.dl-export.pdf', $vendor->id) }}">
                                                    <i class="fas fa-file-pdf"></i> Export PDF
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle"
                                           type="checkbox"
                                           data-id="{{ $vendor->id }}"
                                           {{ $vendor->is_verified ? 'checked' : '' }}>
                                    <label class="form-check-label">
                                        {{ $vendor->is_verified ? 'Verified' : 'Pending' }}
                                    </label>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('vendors.show', $vendor->id) }}"
                                   class="btn btn-sm btn-info"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No vendors found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $vendors->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img class="image-modal-content" id="modalImage" onclick="event.stopPropagation()">
    <div class="image-caption" id="imageCaption"></div>
</div>
@endsection

@section('custom_js')
<script>
$(document).ready(function() {
    // Status Toggle
    $('.status-toggle').on('change', function() {
        const vendorId = $(this).data('id');
        const isChecked = $(this).is(':checked');
        const toggleElement = $(this);
        const label = $(this).next('label');

        $.ajax({
            url: `/vendors/${vendorId}/toggle-status`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                toggleElement.prop('disabled', true);
            },
            success: function(response) {
                if(response.success) {
                    label.text(response.is_verified ? 'Verified' : 'Pending');
                    toastr.success(response.message);
                } else {
                    toastr.error('Failed to update status');
                    toggleElement.prop('checked', !isChecked);
                }
            },
            error: function() {
                toastr.error('Error updating status');
                toggleElement.prop('checked', !isChecked);
            },
            complete: function() {
                toggleElement.prop('disabled', false);
            }
        });
    });
});

// Export Functions
function exportData(type) {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form)).toString();

    let url = '';
    switch(type) {
        case 'excel':
            url = '{{ route("vendors.export.excel") }}';
            break;
        case 'csv':
            url = '{{ route("vendors.export.csv") }}';
            break;
        case 'pdf':
            url = '{{ route("vendors.export.pdf") }}';
            break;
    }

    window.location.href = url + (params ? '?' + params : '');
}

// Image Modal Functions
function showImage(imageSrc, caption) {
    document.getElementById('imageModal').style.display = 'block';
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageCaption').textContent = caption || '';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
});
</script>
@endsection
