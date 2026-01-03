@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .badge {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="table-section" class="table-container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-truck me-2"></i>
                        Truck Types Management
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('truck-types.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Truck Type
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="80">ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th width="100">Status</th>
                            <th width="100">Sort Order</th>
                            <th width="180">Created At</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($truckTypes as $type)
                            <tr>
                                <td>{{ $type->id }}</td>
                                <td><strong>{{ $type->name }}</strong></td>
                                <td>{{ $type->description ?? 'N/A' }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" type="checkbox"
                                            data-id="{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $type->sort_order }}</span>
                                </td>
                                <td>{{ $type->created_at ? $type->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('truck-types.edit', $type->id) }}" class="btn btn-sm btn-warning"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('truck-types.destroy', $type->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this truck type?');">
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
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No truck types found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        $(document).ready(function() {
            // Status Toggle
            $('.status-toggle').on('change', function() {
                const typeId = $(this).data('id');
                const isChecked = $(this).is(':checked');
                const toggleElement = $(this);

                $.ajax({
                    url: `/truck-types/${typeId}/toggle-status`,
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
