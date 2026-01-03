@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .required-field {
            color: #dc3545;
        }

        .feature-input-group {
            margin-bottom: 10px;
        }

        .icon-preview {
            font-size: 48px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="form-section" class="form-section">
            <div class="row mb-3">
                <div class="col-md-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('user-types.index') }}">User Types</a></li>
                            <li class="breadcrumb-item active">Edit #{{ $userType->id }}</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <h2 class="section-title">
                <i class="fas fa-edit me-2"></i>
                Edit User Type: {{ $userType->title }}
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

            <form action="{{ route('user-types.update', $userType->id) }}" method="POST" id="userTypeForm">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Type Key -->
                    <div class="col-md-6 mb-3">
                        <label for="type_key" class="form-label">
                            Type Key <span class="required-field">*</span>
                        </label>
                        <input type="text" class="form-control @error('type_key') is-invalid @enderror" id="type_key"
                            name="type_key" value="{{ old('type_key', $userType->type_key) }}" required>
                        <small class="text-muted">Unique identifier (lowercase, underscore separated)</small>
                        @error('type_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Title -->
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">
                            Title <span class="required-field">*</span>
                        </label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                            name="title" value="{{ old('title', $userType->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Subtitle -->
                    <div class="col-md-6 mb-3">
                        <label for="subtitle" class="form-label">Subtitle</label>
                        <input type="text" class="form-control @error('subtitle') is-invalid @enderror" id="subtitle"
                            name="subtitle" value="{{ old('subtitle', $userType->subtitle) }}">
                        @error('subtitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Icon -->
                    <div class="col-md-6 mb-3">
                        <label for="icon" class="form-label">Icon (Emoji)</label>
                        <input type="text" class="form-control @error('icon') is-invalid @enderror" id="icon"
                            name="icon" value="{{ old('icon', $userType->icon) }}" maxlength="10">
                        @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Icon Preview -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Icon Preview</label>
                        <div class="icon-preview" id="iconPreview">
                            <span id="iconDisplay">{{ $userType->icon ?? 'No icon' }}</span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                            rows="3">{{ old('description', $userType->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Features -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Features</label>
                        <div id="featuresContainer">
                            @php
                                $features = old('features', $userType->features ?? []);
                                if (!is_array($features)) {
                                    $features = [];
                                }
                            @endphp

                            @if (count($features) > 0)
                                @foreach ($features as $feature)
                                    <div class="feature-input-group">
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="features[]"
                                                value="{{ $feature }}">
                                            <button class="btn btn-danger remove-feature" type="button">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="feature-input-group">
                                    <div class="input-group mb-2">
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

                    <!-- Display Order -->
                    <div class="col-md-6 mb-3">
                        <label for="display_order" class="form-label">
                            Display Order <span class="required-field">*</span>
                        </label>
                        <input type="number" class="form-control @error('display_order') is-invalid @enderror"
                            id="display_order" name="display_order"
                            value="{{ old('display_order', $userType->display_order) }}" min="1" required>
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Is Active -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                value="1" {{ old('is_active', $userType->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Update User Type
                    </button>
                    <a href="{{ route('user-types.index') }}" class="btn btn-secondary">
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
            // Icon Preview
            $('#icon').on('input', function() {
                const icon = $(this).val();
                $('#iconDisplay').text(icon || 'No icon selected');
            });

            // Add Feature
            $('#addFeature').on('click', function() {
                const featureHtml = `
            <div class="feature-input-group">
                <div class="input-group mb-2">
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
