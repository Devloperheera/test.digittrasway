@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .required-field {
            color: #dc3545;
        }
        .feature-input-group {
            margin-bottom: 10px;
        }
        .color-preview-box {
            width: 60px;
            height: 40px;
            border-radius: 5px;
            border: 2px solid #ddd;
            cursor: pointer;
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
                            <li class="breadcrumb-item"><a href="{{ route('plans.index') }}">Plans</a></li>
                            <li class="breadcrumb-item active">Create New</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <h2 class="section-title">
                <i class="fas fa-plus-circle me-2"></i>
                Add New Subscription Plan
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

            <form action="{{ route('plans.store') }}" method="POST" id="planForm">
                @csrf

                <div class="row">
                    <!-- Plan Name -->
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">
                            Plan Name <span class="required-field">*</span>
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name') }}" placeholder="e.g., Starter Plan" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Price -->
                    <div class="col-md-3 mb-3">
                        <label for="price" class="form-label">
                            Price (₹) <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('price') is-invalid @enderror" id="price"
                            name="price" value="{{ old('price', 0) }}" min="0" step="0.01" placeholder="0.00"
                            required>
                        <small class="text-muted">Enter 0 for custom pricing</small>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Duration Type -->
                    <div class="col-md-3 mb-3">
                        <label for="duration_type" class="form-label">
                            Duration <span class="required-field">*</span>
                        </label>
                        <select class="form-select @error('duration_type') is-invalid @enderror" id="duration_type"
                            name="duration_type" required>
                            <option value="">Select Duration</option>
                            <option value="daily" {{ old('duration_type') == 'daily' ? 'selected' : '' }} data-days="1">Daily</option>
                            <option value="weekly" {{ old('duration_type') == 'weekly' ? 'selected' : '' }} data-days="7">Weekly</option>
                            <option value="monthly" {{ old('duration_type') == 'monthly' ? 'selected' : '' }} data-days="30">Monthly</option>
                            <option value="quarterly" {{ old('duration_type') == 'quarterly' ? 'selected' : '' }} data-days="90">Quarterly</option>
                            <option value="half_yearly" {{ old('duration_type') == 'half_yearly' ? 'selected' : '' }} data-days="180">Half Yearly</option>
                            <option value="yearly" {{ old('duration_type') == 'yearly' ? 'selected' : '' }} data-days="365">Yearly</option>
                        </select>
                        @error('duration_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Duration Days -->
                    <div class="col-md-3 mb-3">
                        <label for="duration_days" class="form-label">
                            Duration Days <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('duration_days') is-invalid @enderror"
                            id="duration_days" name="duration_days" value="{{ old('duration_days', 30) }}" min="1" required readonly>
                        <small class="text-muted">Auto-filled based on duration type</small>
                        @error('duration_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                            rows="3" placeholder="Enter plan description...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Features -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Features</label>
                        <div id="featuresContainer">
                            @if (old('features'))
                                @foreach (old('features') as $index => $feature)
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
                                            placeholder="Enter feature">
                                        <button class="btn btn-danger remove-feature" type="button">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="addFeature">
                            <i class="fas fa-plus"></i> Add Feature
                        </button>
                    </div>

                    <!-- Button Text -->
                    <div class="col-md-4 mb-3">
                        <label for="button_text" class="form-label">
                            Button Text <span class="required-field">*</span>
                        </label>
                        <input type="text" class="form-control @error('button_text') is-invalid @enderror"
                            id="button_text" name="button_text" value="{{ old('button_text', 'Choose Plan') }}"
                            placeholder="e.g., Choose Plan" required>
                        @error('button_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Button Color -->
                    <div class="col-md-4 mb-3">
                        <label for="button_color" class="form-label">
                            Button Color <span class="required-field">*</span>
                        </label>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="color"
                                class="form-control form-control-color @error('button_color') is-invalid @enderror"
                                id="button_color" name="button_color" value="{{ old('button_color', '#4CAF50') }}"
                                required>
                            <input type="text" class="form-control" id="color_hex"
                                value="{{ old('button_color', '#4CAF50') }}" readonly>
                        </div>
                        @error('button_color')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Contact Info -->
                    <div class="col-md-4 mb-3">
                        <label for="contact_info" class="form-label">
                            Contact Email <small>(for custom plans)</small>
                        </label>
                        <input type="email" class="form-control @error('contact_info') is-invalid @enderror"
                            id="contact_info" name="contact_info" value="{{ old('contact_info') }}"
                            placeholder="sales@yourcompany.com">
                        @error('contact_info')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sort Order -->
                    <div class="col-md-3 mb-3">
                        <label for="sort_order" class="form-label">
                            Sort Order <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                            id="sort_order" name="sort_order" value="{{ old('sort_order', 1) }}" min="1"
                            required>
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Is Popular -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Popular Plan</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_popular" name="is_popular"
                                value="1" {{ old('is_popular') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_popular">
                                Mark as Popular
                            </label>
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Save Plan
                    </button>
                    <a href="{{ route('plans.index') }}" class="btn btn-secondary">
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
                $('#color_hex').val($(this).val());
            });

            // Auto-fill duration days based on duration type
            $('#duration_type').on('change', function() {
                var days = $(this).find('option:selected').data('days');
                if (days) {
                    $('#duration_days').val(days);
                }
            });

            // Trigger change on page load if old value exists
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
        });
    </script>
@endsection
