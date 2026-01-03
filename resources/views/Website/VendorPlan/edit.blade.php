@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .required-field {
            color: #dc3545;
        }

        .feature-input-group {
            margin-bottom: 10px;
        }

        .form-section {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .section-title {
            color: #265b6b;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .duration-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
                            <li class="breadcrumb-item"><a href="{{ route('vendor-plans.index') }}">Vendor Plans</a></li>
                            <li class="breadcrumb-item active">Create New</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <h2 class="section-title">
                <i class="fas fa-plus-circle me-2"></i>
                Add New Vendor Plan
            </h2>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('vendor-plans.store') }}" method="POST" id="planForm">
                @csrf

                <div class="row">
                    <!-- Name -->
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-tag me-1"></i>
                            Plan Name <span class="required-field">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name') }}" placeholder="e.g., Basic Plan, Pro Plan" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Price -->
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">
                            <i class="fas fa-rupee-sign me-1"></i>
                            Price (₹) <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('price') is-invalid @enderror" id="price"
                            name="price" value="{{ old('price', 0) }}" min="0" step="0.01" placeholder="0.00" required>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Duration Type -->
                    <div class="col-md-6 mb-3">
                        <label for="duration_type" class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Duration Type <span class="required-field">*</span>
                        </label>
                        <select class="form-select @error('duration_type') is-invalid @enderror" id="duration_type"
                            name="duration_type" required>
                            <option value="">-- Select Duration --</option>
                            <option value="daily" {{ old('duration_type') == 'daily' ? 'selected' : '' }} data-days="1">
                                Daily (1 day)
                            </option>
                            <option value="weekly" {{ old('duration_type') == 'weekly' ? 'selected' : '' }} data-days="7">
                                Weekly (7 days)
                            </option>
                            <option value="monthly" {{ old('duration_type') == 'monthly' ? 'selected' : '' }} data-days="30">
                                Monthly (30 days)
                            </option>
                            <option value="quarterly" {{ old('duration_type') == 'quarterly' ? 'selected' : '' }} data-days="90">
                                Quarterly (90 days / 3 months)
                            </option>
                            <option value="half_yearly" {{ old('duration_type') == 'half_yearly' ? 'selected' : '' }} data-days="180">
                                Half Yearly (180 days / 6 months)
                            </option>
                            <option value="yearly" {{ old('duration_type') == 'yearly' ? 'selected' : '' }} data-days="365">
                                Yearly (365 days / 1 year)
                            </option>
                        </select>
                        @error('duration_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Duration Days -->
                    <div class="col-md-6 mb-3">
                        <label for="duration_days" class="form-label">
                            <i class="fas fa-calendar-day me-1"></i>
                            Duration Days <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('duration_days') is-invalid @enderror"
                            id="duration_days" name="duration_days" value="{{ old('duration_days', 30) }}" min="1"
                            required readonly>
                        <small class="duration-info">
                            <i class="fas fa-info-circle me-1"></i>Auto-filled based on duration type
                        </small>
                        @error('duration_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left me-1"></i>
                            Description
                        </label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                            rows="3" placeholder="Brief description of the plan">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Features -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">
                            <i class="fas fa-list-check me-1"></i>
                            Features
                        </label>
                        <div id="featuresContainer">
                            @if (old('features'))
                                @foreach (old('features') as $feature)
                                    <div class="feature-input-group">
                                        <div class="input-group mb-2">
                                            <span class="input-group-text"><i class="fas fa-check"></i></span>
                                            <input type="text" class="form-control" name="features[]"
                                                value="{{ $feature }}" placeholder="Enter feature">
                                            <button class="btn btn-danger remove-feature" type="button">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="feature-input-group">
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="fas fa-check"></i></span>
                                        <input type="text" class="form-control" name="features[]"
                                            placeholder="e.g., Basic support, 5 listings">
                                        <button class="btn btn-danger remove-feature" type="button">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="addFeature">
                            <i class="fas fa-plus"></i> Add Feature
                        </button>
                    </div>

                    <!-- Button Text -->
                    <div class="col-md-4 mb-3">
                        <label for="button_text" class="form-label">
                            <i class="fas fa-mouse-pointer me-1"></i>
                            Button Text <span class="required-field">*</span>
                        </label>
                        <input type="text" class="form-control @error('button_text') is-invalid @enderror"
                            id="button_text" name="button_text" value="{{ old('button_text', 'Choose Plan') }}"
                            placeholder="Choose Plan" required>
                        @error('button_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Button Color -->
                    <div class="col-md-4 mb-3">
                        <label for="button_color" class="form-label">
                            <i class="fas fa-palette me-1"></i>
                            Button Color <span class="required-field">*</span>
                        </label>
                        <div class="d-flex gap-2">
                            <input type="color"
                                class="form-control form-control-color @error('button_color') is-invalid @enderror"
                                id="button_color" name="button_color" value="{{ old('button_color', '#4CAF50') }}"
                                required style="width: 70px;">
                            <input type="text" class="form-control" id="color_hex"
                                value="{{ old('button_color', '#4CAF50') }}" readonly>
                        </div>
                        @error('button_color')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sort Order -->
                    <div class="col-md-4 mb-3">
                        <label for="sort_order" class="form-label">
                            <i class="fas fa-sort-numeric-up me-1"></i>
                            Sort Order <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                            id="sort_order" name="sort_order" value="{{ old('sort_order', 1) }}" min="1"
                            placeholder="1" required>
                        <small class="text-muted">Lower number appears first</small>
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Is Popular -->
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_popular" name="is_popular"
                                value="1" {{ old('is_popular') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_popular">
                                <i class="fas fa-star text-warning me-1"></i>
                                Mark as Popular
                            </label>
                        </div>
                        <small class="text-muted">Popular plans will be highlighted</small>
                    </div>

                    <!-- Is Active -->
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-toggle-on text-success me-1"></i>
                                Active
                            </label>
                        </div>
                        <small class="text-muted">Inactive plans won't be visible to vendors</small>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Save Plan
                    </button>
                    <a href="{{ route('vendor-plans.index') }}" class="btn btn-secondary">
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
            // Color picker sync
            $('#button_color').on('input', function() {
                $('#color_hex').val($(this).val().toUpperCase());
            });

            // Auto-fill duration days based on duration type
            $('#duration_type').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const days = selectedOption.data('days');

                if (days) {
                    $('#duration_days').val(days);
                }
            });

            // Trigger on page load if old value exists
            if ($('#duration_type').val()) {
                $('#duration_type').trigger('change');
            }

            // Add Feature
            $('#addFeature').on('click', function() {
                const featureHtml = `
                    <div class="feature-input-group">
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="fas fa-check"></i></span>
                            <input type="text"
                                   class="form-control"
                                   name="features[]"
                                   placeholder="Enter feature">
                            <button class="btn btn-danger remove-feature" type="button">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
                $('#featuresContainer').append(featureHtml);
            });

            // Remove Feature
            $(document).on('click', '.remove-feature', function() {
                if ($('.feature-input-group').length > 1) {
                    $(this).closest('.feature-input-group').remove();
                } else {
                    toastr.warning('At least one feature field is required');
                }
            });

            // Form validation
            $('#planForm').on('submit', function(e) {
                const features = $('input[name="features[]"]').filter(function() {
                    return $(this).val().trim() !== '';
                });

                if (features.length === 0) {
                    e.preventDefault();
                    toastr.error('Please add at least one feature');
                    return false;
                }
            });
        });
    </script>
@endsection
