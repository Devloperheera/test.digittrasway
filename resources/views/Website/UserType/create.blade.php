@extends('Website.Layout.master')

@section('custom_css')
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div class="form-section">
            <div class="row mb-3">
                <div class="col-md-12">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('user-types.index') }}">User Types</a></li>
                            <li class="breadcrumb-item active">Create New</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="">
                <h2 class="section-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add New User Type
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

                <form action="{{ route('user-types.store') }}" method="POST" id="userTypeForm">
                    @csrf

                    <div class="row">
                        <!-- Type Key -->
                        <div class="col-md-6 mb-3">
                            <label for="type_key" class="form-label">
                                Type Key <span class="required-field">*</span>
                            </label>
                            <input type="text" class="form-control @error('type_key') is-invalid @enderror"
                                id="type_key" name="type_key" value="{{ old('type_key') }}" placeholder="e.g., fleet_owner"
                                required>
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
                                name="title" value="{{ old('title') }}" placeholder="e.g., Fleet Owner" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Subtitle -->
                        <div class="col-md-6 mb-3">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <input type="text" class="form-control @error('subtitle') is-invalid @enderror"
                                id="subtitle" name="subtitle" value="{{ old('subtitle') }}"
                                placeholder="e.g., BUSINESS & OPERATIONS">
                            @error('subtitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Icon -->
                        <div class="col-md-6 mb-3">
                            <label for="icon" class="form-label">Icon (Emoji)</label>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror" id="icon"
                                name="icon" value="{{ old('icon') }}" placeholder="🏢" maxlength="10">
                            <small class="text-muted">Paste emoji here</small>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Icon Preview -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Icon Preview</label>
                            <div class="icon-preview" id="iconPreview">
                                <span id="iconDisplay">No icon selected</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                rows="3" placeholder="Enter user type description...">{{ old('description') }}</textarea>
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
                                id="display_order" name="display_order" value="{{ old('display_order', 1) }}"
                                min="1" required>
                            @error('display_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Is Active -->
                        <div class="col-md-6 mb-3">
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
                            Save User Type
                        </button>
                        <a href="{{ route('user-types.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        $(document).ready(function() {
            // Icon Preview
            $('#icon').on('input', function() {
                const icon = $(this).val();
                if (icon) {
                    $('#iconDisplay').text(icon);
                } else {
                    $('#iconDisplay').text('No icon selected');
                }
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

            // Type Key Auto-generation
            $('#title').on('input', function() {
                const title = $(this).val();
                const typeKey = title.toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '')
                    .replace(/\s+/g, '_');
                if (!$('#type_key').val()) {
                    $('#type_key').val(typeKey);
                }
            });
        });
    </script>
@endsection
