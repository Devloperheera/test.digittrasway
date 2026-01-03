@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .required-field {
            color: #dc3545;
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                                <a href="{{ route('truck-specifications.index') }}">Truck Specifications</a>
                            </li>
                            <li class="breadcrumb-item active">Edit #{{ $specification->id }}</li>
                        </ol>
                    </nav>
                </div>
            </div>


            <h2 class="section-title">
                <i class="fas fa-edit me-2"></i>
                Edit Truck Specification
            </h2>

            <div class="info-box">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Enter truck dimensions and specifications accurately for proper matching
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('truck-specifications.update', $specification->id) }}" method="POST"
                id="specificationForm">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Truck Type -->
                    <div class="col-md-6 mb-3">
                        <label for="truck_type_id" class="form-label">
                            Truck Type <span class="required-field">*</span>
                        </label>
                        <select class="form-select @error('truck_type_id') is-invalid @enderror" id="truck_type_id"
                            name="truck_type_id" required>
                            <option value="">Select Truck Type</option>
                            @foreach ($truckTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('truck_type_id', $specification->truck_type_id) == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('truck_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tyre Count -->
                    <div class="col-md-6 mb-3">
                        <label for="tyre_count" class="form-label">
                            Number of Tyres <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('tyre_count') is-invalid @enderror" id="tyre_count"
                            name="tyre_count" value="{{ old('tyre_count', $specification->tyre_count) }}" min="2"
                            required>
                        @error('tyre_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Length -->
                    <div class="col-md-4 mb-3">
                        <label for="length" class="form-label">
                            Length <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('length') is-invalid @enderror" id="length"
                            name="length" value="{{ old('length', $specification->length) }}" min="0"
                            step="0.01" required>
                        @error('length')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Length Unit -->
                    <div class="col-md-2 mb-3">
                        <label for="length_unit" class="form-label">
                            Unit <span class="required-field">*</span>
                        </label>
                        <select class="form-select @error('length_unit') is-invalid @enderror" id="length_unit"
                            name="length_unit" required>
                            <option value="ft"
                                {{ old('length_unit', $specification->length_unit) == 'ft' ? 'selected' : '' }}>
                                Feet
                            </option>
                            <option value="m"
                                {{ old('length_unit', $specification->length_unit) == 'm' ? 'selected' : '' }}>
                                Meters
                            </option>
                        </select>
                        @error('length_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Height -->
                    <div class="col-md-4 mb-3">
                        <label for="height" class="form-label">
                            Height <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('height') is-invalid @enderror" id="height"
                            name="height" value="{{ old('height', $specification->height) }}" min="0"
                            step="0.01" required>
                        @error('height')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Height Unit -->
                    <div class="col-md-2 mb-3">
                        <label for="height_unit" class="form-label">
                            Unit <span class="required-field">*</span>
                        </label>
                        <select class="form-select @error('height_unit') is-invalid @enderror" id="height_unit"
                            name="height_unit" required>
                            <option value="ft"
                                {{ old('height_unit', $specification->height_unit) == 'ft' ? 'selected' : '' }}>
                                Feet
                            </option>
                            <option value="m"
                                {{ old('height_unit', $specification->height_unit) == 'm' ? 'selected' : '' }}>
                                Meters
                            </option>
                        </select>
                        @error('height_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Max Weight -->
                    <div class="col-md-6 mb-3">
                        <label for="max_weight" class="form-label">
                            Maximum Weight (tons) <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('max_weight') is-invalid @enderror" id="max_weight"
                            name="max_weight" value="{{ old('max_weight', $specification->max_weight) }}" min="0"
                            step="0.01" required>
                        @error('max_weight')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Is Active -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                value="1" {{ old('is_active', $specification->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Specification Preview -->
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <h6><i class="fas fa-eye me-2"></i>Specification Preview</h6>
                        <p class="mb-0" id="specPreview">Loading...</p>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Update Specification
                    </button>
                    <a href="{{ route('truck-specifications.index') }}" class="btn btn-secondary">
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
                const truckType = $('#truck_type_id option:selected').text();
                const length = $('#length').val();
                const lengthUnit = $('#length_unit').val();
                const height = $('#height').val();
                const heightUnit = $('#height_unit').val();
                const tyres = $('#tyre_count').val();
                const weight = $('#max_weight').val();

                if (length && height && tyres && weight) {
                    $('#specPreview').html(`
                <strong>${truckType}</strong> -
                <span class="badge bg-info">Length: ${length} ${lengthUnit}</span>
                <span class="badge bg-info">Height: ${height} ${heightUnit}</span>
                <span class="badge bg-info">Tyres: ${tyres}</span>
                <span class="badge bg-success">Max Weight: ${weight} tons</span>
            `);
                }
            }

            // Initial preview
            updatePreview();

            $('#truck_type_id, #length, #length_unit, #height, #height_unit, #tyre_count, #max_weight')
                .on('input change', updatePreview);
        });
    </script>
@endsection
