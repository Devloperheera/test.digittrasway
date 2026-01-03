@extends('Website.Layout.master')

@section('custom_css')
<style>
    .required-field {
        color: #dc3545;
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
    <div class="form-section">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('vehicle-categories.index') }}">Vehicle Categories</a>
                    </li>
                    <li class="breadcrumb-item active">Edit #{{ $category->id }}</li>
                </ol>
            </nav>
        </div>
    </div>

    
        <h2 class="section-title">
            <i class="fas fa-edit me-2"></i>
            Edit Vehicle Category: {{ $category->category_name }}
        </h2>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('vehicle-categories.update', $category->id) }}" method="POST" id="categoryForm">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Category Key -->
                <div class="col-md-6 mb-3">
                    <label for="category_key" class="form-label">
                        Category Key <span class="required-field">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('category_key') is-invalid @enderror"
                           id="category_key"
                           name="category_key"
                           value="{{ old('category_key', $category->category_key) }}"
                           required>
                    <small class="text-muted">Unique identifier (lowercase, underscore separated)</small>
                    @error('category_key')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Category Name -->
                <div class="col-md-6 mb-3">
                    <label for="category_name" class="form-label">
                        Category Name <span class="required-field">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('category_name') is-invalid @enderror"
                           id="category_name"
                           name="category_name"
                           value="{{ old('category_name', $category->category_name) }}"
                           required>
                    @error('category_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Description -->
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description"
                              name="description"
                              rows="3">{{ old('description', $category->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Icon -->
                <div class="col-md-6 mb-3">
                    <label for="icon" class="form-label">Icon (Emoji)</label>
                    <input type="text"
                           class="form-control @error('icon') is-invalid @enderror"
                           id="icon"
                           name="icon"
                           value="{{ old('icon', $category->icon) }}"
                           maxlength="10">
                    <small class="text-muted">Paste emoji here</small>
                    @error('icon')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Display Order -->
                <div class="col-md-6 mb-3">
                    <label for="display_order" class="form-label">
                        Display Order <span class="required-field">*</span>
                    </label>
                    <input type="number"
                           class="form-control @error('display_order') is-invalid @enderror"
                           id="display_order"
                           name="display_order"
                           value="{{ old('display_order', $category->display_order) }}"
                           min="1"
                           required>
                    @error('display_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Icon Preview -->
                <div class="col-md-12 mb-3">
                    <label class="form-label">Icon Preview</label>
                    <div class="icon-preview" id="iconPreview">
                        <span id="iconDisplay">{{ $category->icon ?? 'No icon' }}</span>
                    </div>
                </div>

                <!-- Is Active -->
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Update Category
                </button>
                <a href="{{ route('vehicle-categories.index') }}" class="btn btn-secondary">
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
});
</script>
@endsection
