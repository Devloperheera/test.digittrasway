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
    <div class="container-fluid mt-4">
        <div id="table-section" class="table-container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-car me-2"></i>
                        Vehicle Categories
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('vehicle-categories.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Category
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
            <form method="GET" action="{{ route('vehicle-categories.index') }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by name, key, description..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('vehicle-categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive mt-5">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Category Key</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Icon</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $category->id }}</td>
                                <td><code>{{ $category->category_key }}</code></td>
                                <td><strong>{{ $category->category_name }}</strong></td>
                                <td>{{ $category->description ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if ($category->icon)
                                        <span style="font-size: 24px;">{{ $category->icon }}</span>
                                    @else
                                        <span class="text-muted">No Icon</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" type="checkbox"
                                            data-id="{{ $category->id }}" {{ $category->is_active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $category->display_order }}</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('vehicle-categories.edit', $category->id) }}"
                                            class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('vehicle-categories.destroy', $category->id) }}"
                                            method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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
                                    <p>No categories found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $categories->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endsection

    @section('custom_js')
        <script>
            $(document).ready(function() {
                // Status Toggle
                $('.status-toggle').on('change', function() {
                    const categoryId = $(this).data('id');
                    const isChecked = $(this).is(':checked');
                    const toggleElement = $(this);

                    $.ajax({
                        url: `/vehicle-categories/${categoryId}/toggle-status`,
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
