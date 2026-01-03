@extends('Website.Layout.master')

@section('custom_css')
<style>
    .document-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
    }

    .file-preview {
        max-width: 150px;
        max-height: 150px;
        border: 2px solid #ddd;
        border-radius: 5px;
        padding: 5px;
        margin-top: 10px;
    }

    .section-header {
        color: #265b6b;
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid #265b6b;
        padding-bottom: 10px;
    }

    .current-document {
        display: inline-block;
        margin-top: 10px;
        padding: 10px 15px;
        background: #e3f2fd;
        border-radius: 5px;
        border: 1px solid #2196F3;
    }

    .document-preview-img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #ddd;
        margin-top: 10px;
    }

    .btn-view-doc {
        font-size: 12px;
        padding: 5px 10px;
        margin-top: 5px;
    }
</style>
@endsection

@section('content')
<div class="content-area">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #265b6b; font-weight: 700;">Edit Employee - {{ $employee->emp_id }}</h2>
            <div>
                <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-info">
                    <i class="fas fa-eye me-2"></i>View Details
                </a>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data" id="employeeForm">
                    @csrf
                    @method('PUT')

                    {{-- Basic Information --}}
                    <div class="section-header">
                        <i class="fas fa-user me-2"></i>Basic Information
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" value="{{ $employee->emp_id }}" disabled>
                            <small class="text-muted">Employee ID cannot be changed</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employee->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $employee->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone) }}" maxlength="15" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation *</label>
                            <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $employee->designation) }}" required>
                            @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department *</label>
                            <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" value="{{ old('department', $employee->department) }}" required>
                            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Joining *</label>
                            <input type="date" name="date_of_joining" class="form-control @error('date_of_joining') is-invalid @enderror" value="{{ old('date_of_joining', $employee->date_of_joining->format('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                            @error('date_of_joining')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salary *</label>
                            <input type="number" name="salary" step="0.01" class="form-control @error('salary') is-invalid @enderror" value="{{ old('salary', $employee->salary) }}" required>
                            @error('salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $employee->address) }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Photo</label>

                            @if($employee->photo)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $employee->photo) }}" alt="Current Photo" class="document-preview-img">
                                <div class="mt-2">
                                    <a href="{{ route('employees.view-document', [$employee->id, 'photo']) }}" target="_blank" class="btn btn-sm btn-primary btn-view-doc">
                                        <i class="fas fa-eye"></i> View Full Size
                                    </a>
                                </div>
                            </div>
                            @else
                            <p class="text-muted">No photo uploaded</p>
                            @endif

                            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*" onchange="previewImage(this, 'photo_preview')">
                            <small class="text-muted">Leave empty to keep current photo</small>
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <img id="photo_preview" class="file-preview" style="display:none;" alt="New Photo Preview">
                        </div>
                    </div>

                    {{-- Document Upload Section --}}
                    <div class="document-section">
                        <div class="section-header">
                            <i class="fas fa-file-upload me-2"></i>Document Uploads
                        </div>

                        <div class="row">
                            {{-- Aadhar Card Front --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhar Card (Front)</label>

                                @if($employee->aadhar_front)
                                <div class="current-document">
                                    <i class="fas fa-file-image text-primary"></i> Document Uploaded
                                    <div class="mt-2">
                                        <a href="{{ route('employees.view-document', [$employee->id, 'aadhar_front']) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('employees.download-document', [$employee->id, 'aadhar_front']) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                                @else
                                <p class="text-muted">No document uploaded</p>
                                @endif

                                <input type="file" name="aadhar_front" class="form-control @error('aadhar_front') is-invalid @enderror mt-2" accept="image/*,application/pdf">
                                <small class="text-muted">Leave empty to keep current document</small>
                                @error('aadhar_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Aadhar Card Back --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhar Card (Back)</label>

                                @if($employee->aadhar_back)
                                <div class="current-document">
                                    <i class="fas fa-file-image text-primary"></i> Document Uploaded
                                    <div class="mt-2">
                                        <a href="{{ route('employees.view-document', [$employee->id, 'aadhar_back']) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('employees.download-document', [$employee->id, 'aadhar_back']) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                                @else
                                <p class="text-muted">No document uploaded</p>
                                @endif

                                <input type="file" name="aadhar_back" class="form-control @error('aadhar_back') is-invalid @enderror mt-2" accept="image/*,application/pdf">
                                <small class="text-muted">Leave empty to keep current document</small>
                                @error('aadhar_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Aadhar Number --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhar Number</label>
                                <input type="text" name="aadhar_number" class="form-control @error('aadhar_number') is-invalid @enderror" value="{{ old('aadhar_number', $employee->aadhar_number) }}" maxlength="12" placeholder="xxxx xxxx xxxx">
                                @error('aadhar_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- PAN Card --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">PAN Card</label>

                                @if($employee->pan_card)
                                <div class="current-document">
                                    <i class="fas fa-file-image text-primary"></i> Document Uploaded
                                    <div class="mt-2">
                                        <a href="{{ route('employees.view-document', [$employee->id, 'pan_card']) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('employees.download-document', [$employee->id, 'pan_card']) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                                @else
                                <p class="text-muted">No document uploaded</p>
                                @endif

                                <input type="file" name="pan_card" class="form-control @error('pan_card') is-invalid @enderror mt-2" accept="image/*,application/pdf">
                                <small class="text-muted">Leave empty to keep current document</small>
                                @error('pan_card')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- PAN Number --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">PAN Number</label>
                                <input type="text" name="pan_number" class="form-control @error('pan_number') is-invalid @enderror" value="{{ old('pan_number', $employee->pan_number) }}" maxlength="10" placeholder="ABCDE1234F" style="text-transform: uppercase;">
                                @error('pan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Driving License --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Driving License</label>

                                @if($employee->driving_license)
                                <div class="current-document">
                                    <i class="fas fa-file-image text-primary"></i> Document Uploaded
                                    <div class="mt-2">
                                        <a href="{{ route('employees.view-document', [$employee->id, 'driving_license']) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('employees.download-document', [$employee->id, 'driving_license']) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                                @else
                                <p class="text-muted">No document uploaded</p>
                                @endif

                                <input type="file" name="driving_license" class="form-control @error('driving_license') is-invalid @enderror mt-2" accept="image/*,application/pdf">
                                <small class="text-muted">Leave empty to keep current document</small>
                                @error('driving_license')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- DL Number --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">DL Number</label>
                                <input type="text" name="dl_number" class="form-control @error('dl_number') is-invalid @enderror" value="{{ old('dl_number', $employee->dl_number) }}" maxlength="20" placeholder="HR01 20190012345">
                                @error('dl_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Address Proof --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address Proof</label>

                                @if($employee->address_proof)
                                <div class="current-document">
                                    <i class="fas fa-file-image text-primary"></i> Document Uploaded
                                    <div class="mt-2">
                                        <a href="{{ route('employees.view-document', [$employee->id, 'address_proof']) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('employees.download-document', [$employee->id, 'address_proof']) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                                @else
                                <p class="text-muted">No document uploaded</p>
                                @endif

                                <input type="file" name="address_proof" class="form-control @error('address_proof') is-invalid @enderror mt-2" accept="image/*,application/pdf">
                                <small class="text-muted">Electricity Bill, Rent Agreement, etc. Leave empty to keep current document</small>
                                @error('address_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Employee
                        </button>
                        <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>View Details
                        </a>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@section('custom_js')
<script>
// Image Preview Function
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);

    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Check if it's a PDF
        if (file.type === 'application/pdf') {
            preview.style.display = 'none';
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };

        reader.readAsDataURL(file);
    }
}

// Auto-format PAN number
document.querySelector('input[name="pan_number"]')?.addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});
</script>
@endsection
