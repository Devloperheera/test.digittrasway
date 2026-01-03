@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .badge {
            padding: 5px 10px;
            font-size: 12px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4 mb-5">
        <div id="table-section" class="table-container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-truck me-2"></i>
                        Vehicle Models
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('vehicle-models.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Model
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filters -->
            <form method="GET" action="{{ route('vehicle-models.index') }}">
                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by model name, description..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active
                            </option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('vehicle-models.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Model Name</th>
                            <th>Description</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($models as $model)
                            <tr>
                                <td>{{ $model->id }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $model->category->category_name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td><strong>{{ $model->model_name }}</strong></td>
                                <td>{{ $model->vehicle_type_desc ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        <i class="fas fa-weight"></i> {{ $model->formatted_capacity }}
                                    </span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" type="checkbox"
                                            data-id="{{ $model->id }}" {{ $model->is_active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $model->display_order }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('vehicle-models.edit', $model->id) }}"
                                            class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('vehicle-models.destroy', $model->id) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No vehicle models found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $models->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        $(document).ready(function() {
            // Status Toggle
            $('.status-toggle').on('change', function() {
                const modelId = $(this).data('id');
                const isChecked = $(this).is(':checked');
                const toggleElement = $(this);

                $.ajax({
                    url: `/vehicle-models/${modelId}/toggle-status`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        toggleElement.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                        } else {
                            toastr.error('Failed to update status');
                            toggleElement.prop('checked', !isChecked);
                        }
                    },
                    error: function() {
                        toastr.error('Error updating status');
                        toggleElement.prop('checked', !isChecked);
                    },
                    complete: function() {
                        toggleElement.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection
