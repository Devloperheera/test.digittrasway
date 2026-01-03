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
                    <li class="breadcrumb-item"><a href="{{ route('distance-pricings.index') }}">Distance Pricings</a></li>
                    <li class="breadcrumb-item active">Edit #{{ $pricing->id }}</li>
                </ol>
            </nav>
        </div>
    </div>


        <h2 class="section-title">
            <i class="fas fa-edit me-2"></i>
            Edit Distance Pricing: {{ $pricing->name }}
        </h2>

        <div class="info-box">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note:</strong> Set distance_to as NULL for unlimited upper range (e.g., 500+ km)
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('distance-pricings.update', $pricing->id) }}" method="POST" id="pricingForm">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Name -->
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">
                        Pricing Name <span class="required-field">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name', $pricing->name) }}"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Price Per KM -->
                <div class="col-md-6 mb-3">
                    <label for="price_per_km" class="form-label">
                        Price per KM (₹) <span class="required-field">*</span>
                    </label>
                    <input type="number"
                           class="form-control @error('price_per_km') is-invalid @enderror"
                           id="price_per_km"
                           name="price_per_km"
                           value="{{ old('price_per_km', $pricing->price_per_km) }}"
                           min="0"
                           step="0.01"
                           required>
                    @error('price_per_km')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Distance From -->
                <div class="col-md-4 mb-3">
                    <label for="distance_from" class="form-label">
                        Distance From (KM) <span class="required-field">*</span>
                    </label>
                    <input type="number"
                           class="form-control @error('distance_from') is-invalid @enderror"
                           id="distance_from"
                           name="distance_from"
                           value="{{ old('distance_from', $pricing->distance_from) }}"
                           min="0"
                           required>
                    @error('distance_from')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Distance To -->
                <div class="col-md-4 mb-3">
                    <label for="distance_to" class="form-label">
                        Distance To (KM) <small class="text-muted">(Leave empty for unlimited)</small>
                    </label>
                    <input type="number"
                           class="form-control @error('distance_to') is-invalid @enderror"
                           id="distance_to"
                           name="distance_to"
                           value="{{ old('distance_to', $pricing->distance_to) }}"
                           min="0">
                    @error('distance_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Minimum Charge -->
                <div class="col-md-4 mb-3">
                    <label for="minimum_charge" class="form-label">
                        Minimum Charge (₹) <span class="required-field">*</span>
                    </label>
                    <input type="number"
                           class="form-control @error('minimum_charge') is-invalid @enderror"
                           id="minimum_charge"
                           name="minimum_charge"
                           value="{{ old('minimum_charge', $pricing->minimum_charge) }}"
                           min="0"
                           step="0.01"
                           required>
                    @error('minimum_charge')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description"
                              name="description"
                              rows="3">{{ old('description', $pricing->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div class="col-md-6 mb-3">
                    <label for="sort_order" class="form-label">
                        Sort Order <span class="required-field">*</span>
                    </label>
                    <input type="number"
                           class="form-control @error('sort_order') is-invalid @enderror"
                           id="sort_order"
                           name="sort_order"
                           value="{{ old('sort_order', $pricing->sort_order) }}"
                           min="1"
                           required>
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Is Active -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $pricing->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <!-- Price Preview -->
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6><i class="fas fa-calculator me-2"></i>Price Preview (for 50 KM)</h6>
                    <p class="mb-0" id="pricePreview">Loading...</p>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Update Distance Pricing
                </button>
                <a href="{{ route('distance-pricings.index') }}" class="btn btn-secondary">
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
    // Price Preview Calculator
    function updatePreview() {
        const pricePerKm = parseFloat($('#price_per_km').val()) || 0;
        const minCharge = parseFloat($('#minimum_charge').val()) || 0;
        const distance = 50;

        if(pricePerKm > 0) {
            const calculatedPrice = distance * pricePerKm;
            const finalPrice = Math.max(calculatedPrice, minCharge);

            $('#pricePreview').html(`
                <strong>For 50 KM:</strong>
                Calculated: ₹${calculatedPrice.toFixed(2)} |
                Minimum: ₹${minCharge.toFixed(2)} |
                <strong class="text-success">Final Price: ₹${finalPrice.toFixed(2)}</strong>
            `);
        }
    }

    // Initial preview
    updatePreview();

    $('#price_per_km, #minimum_charge').on('input', updatePreview);

    // Validate distance range
    $('#distance_to').on('input', function() {
        const distanceFrom = parseInt($('#distance_from').val()) || 0;
        const distanceTo = parseInt($(this).val());

        if(distanceTo && distanceTo <= distanceFrom) {
            $(this).addClass('is-invalid');
            toastr.warning('Distance To must be greater than Distance From');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>
@endsection
