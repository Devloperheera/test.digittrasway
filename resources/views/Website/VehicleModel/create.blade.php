@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .required-field {
            color: #dc3545;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div class="form-section">
            <div class="row mb-3">
                <div class="col-md-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('vehicle-models.index') }}">Vehicle Models</a>
                            </li>
                            <li class="breadcrumb-item active">Create New</li>
                        </ol>
                    </nav>
                </div>
            </div>


            <h2 class="section-title">
                <i class="fas fa-plus-circle me-2"></i>
                Add New Vehicle Model
            </h2>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('vehicle-models.store') }}" method="POST" id="vehicleModelForm">
                @csrf

                <div class="row">
                    <!-- Vehicle Category -->
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">
                            Vehicle Category <span class="required-field">*</span>
                        </label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id"
                            name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Model Name -->
                    <div class="col-md-6 mb-3">
                        <label for="model_name" class="form-label">
                            Model Name <span class="required-field">*</span>
                        </label>
                        <input type="text" class="form-control @error('model_name') is-invalid @enderror" id="model_name"
                            name="model_name" value="{{ old('model_name') }}" placeholder="e.g., Tata Ace" required>
                        @error('model_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Vehicle Type Description -->
                    <div class="col-md-12 mb-3">
                        <label for="vehicle_type_desc" class="form-label">
                            Vehicle Type Description
                        </label>
                        <textarea class="form-control @error('vehicle_type_desc') is-invalid @enderror" id="vehicle_type_desc"
                            name="vehicle_type_desc" rows="3" placeholder="Enter detailed description of the vehicle type...">{{ old('vehicle_type_desc') }}</textarea>
                        @error('vehicle_type_desc')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Carry Capacity -->
                    <div class="col-md-6 mb-3">
                        <label for="carry_capacity_tons" class="form-label">
                            Carry Capacity (tons) <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('carry_capacity_tons') is-invalid @enderror"
                            id="carry_capacity_tons" name="carry_capacity_tons" value="{{ old('carry_capacity_tons') }}"
                            min="0" step="0.01" placeholder="e.g., 1.5" required>
                        @error('carry_capacity_tons')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Display Order -->
                    <div class="col-md-6 mb-3">
                        <label for="display_order" class="form-label">
                            Display Order <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('display_order') is-invalid @enderror"
                            id="display_order" name="display_order" value="{{ old('display_order', 1) }}" min="1"
                            required>
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Is Active -->
                    <div class="col-md-12 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Preview Card -->
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <h6><i class="fas fa-eye me-2"></i>Model Preview</h6>
                        <p class="mb-0" id="modelPreview">
                            <span class="badge bg-secondary">Fill in the details to see preview</span>
                        </p>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Save Vehicle Model
                    </button>
                    <a href="{{ route('vehicle-models.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        $(document).ready(function() {
            // Live Preview
            function updatePreview() {
                const category = $('#category_id option:selected').text();
                const modelName = $('#model_name').val();
                const capacity = $('#carry_capacity_tons').val();

                if (modelName && capacity) {
                    $('#modelPreview').html(`
                <strong>${modelName}</strong> - 
                <span class="badge bg-info">${category}</span>
                <span class="badge bg-success"><i class="fas fa-weight"></i> ${capacity} tons</span>
            `);
                }
            }

            $('#category_id, #model_name, #carry_capacity_tons').on('input change', updatePreview);
        });
    </script>
@endsection
