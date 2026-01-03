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
        display: none;
    }

    .section-header {
        color: #265b6b;
        font-weight: 600;
        margin-bottom: 15px;
        border-bottom: 2px solid #265b6b;
        padding-bottom: 10px;
    }
</style>
@endsection

@section('content')
<div class="content-area">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color: #265b6b; font-weight: 700;">Add New Employee</h2>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="employeeForm">
                    @csrf

                    {{-- Basic Information --}}
                    <div class="section-header">
                        <i class="fas fa-user me-2"></i>Basic Information
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee ID *</label>
                            <input type="text" class="form-control" value="Auto Generated (e.g., DTE0001)" readonly>
                            <small class="text-muted">Will be auto-generated on save</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" maxlength="15" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation *</label>
                            <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation') }}" required>
                            @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department *</label>
                            <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" value="{{ old('department') }}" required>
                            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Joining *</label>
                            <input type="date" name="date_of_joining" class="form-control @error('date_of_joining') is-invalid @enderror" value="{{ old('date_of_joining') }}" max="{{ date('Y-m-d') }}" required>
                            @error('date_of_joining')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salary *</label>
                            <input type="number" name="salary" step="0.01" class="form-control @error('salary') is-invalid @enderror" value="{{ old('salary') }}" required>
                            @error('salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Photo</label>
                            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*" onchange="previewImage(this, 'photo_preview')">
                            @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <img id="photo_preview" class="file-preview" alt="Photo Preview">
                        </div>
                    </div>

                    {{-- Document Upload Section --}}
                    <div class="document-section">
                        <div class="section-header">
                            <i class="fas fa-file-upload me-2"></i>Document Uploads
                        </div>

                        <div class="row">
                            {{-- Aadhar Card --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhar Card (Front)</label>
                                <input type="file" name="aadhar_front" class="form-control @error('aadhar_front') is-invalid @enderror" accept="image/*,application/pdf" onchange="previewImage(this, 'aadhar_front_preview')">
                                @error('aadhar_front')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <img id="aadhar_front_preview" class="file-preview" alt="Aadhar Front Preview">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhar Card (Back)</label>
                                <input type="file" name="aadhar_back" class="form-control @error('aadhar_back') is-invalid @enderror" accept="image/*,application/pdf" onchange="previewImage(this, 'aadhar_back_preview')">
                                @error('aadhar_back')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <img id="aadhar_back_preview" class="file-preview" alt="Aadhar Back Preview">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhar Number</label>
                                <input type="text" name="aadhar_number" class="form-control @error('aadhar_number') is-invalid @enderror" value="{{ old('aadhar_number') }}" maxlength="12" placeholder="xxxx xxxx xxxx">
                                @error('aadhar_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- PAN Card --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">PAN Card</label>
                                <input type="file" name="pan_card" class="form-control @error('pan_card') is-invalid @enderror" accept="image/*,application/pdf" onchange="previewImage(this, 'pan_preview')">
                                @error('pan_card')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <img id="pan_preview" class="file-preview" alt="PAN Preview">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">PAN Number</label>
                                <input type="text" name="pan_number" class="form-control @error('pan_number') is-invalid @enderror" value="{{ old('pan_number') }}" maxlength="10" placeholder="ABCDE1234F" style="text-transform: uppercase;">
                                @error('pan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Driving License --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Driving License</label>
                                <input type="file" name="driving_license" class="form-control @error('driving_license') is-invalid @enderror" accept="image/*,application/pdf" onchange="previewImage(this, 'dl_preview')">
                                @error('driving_license')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <img id="dl_preview" class="file-preview" alt="DL Preview">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">DL Number</label>
                                <input type="text" name="dl_number" class="form-control @error('dl_number') is-invalid @enderror" value="{{ old('dl_number') }}" maxlength="20" placeholder="HR01 20190012345">
                                @error('dl_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Address Proof --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address Proof</label>
                                <input type="file" name="address_proof" class="form-control @error('address_proof') is-invalid @enderror" accept="image/*,application/pdf" onchange="previewImage(this, 'address_proof_preview')">
                                @error('address_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Electricity Bill, Rent Agreement, etc.</small>
                                <img id="address_proof_preview" class="file-preview" alt="Address Proof Preview">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Employee
                        </button>
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
