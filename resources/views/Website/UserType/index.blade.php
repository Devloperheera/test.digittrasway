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

        .feature-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin: 2px;
            display: inline-block;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="form-section" class="form-section">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-user-tag me-2"></i>
                        User Types Management
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('user-types.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New User Type
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
                            <th>ID</th>
                            <th>Type Key</th>
                            <th>Title</th>
                            <th>Subtitle</th>
                            <th>Icon</th>
                            <th>Features</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userTypes as $type)
                            <tr>
                                <td>{{ $type->id }}</td>
                                <td><code>{{ $type->type_key }}</code></td>
                                <td><strong>{{ $type->title }}</strong></td>
                                <td>{{ $type->subtitle ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if ($type->icon)
                                        <span style="font-size: 24px;">{{ $type->icon }}</span>
                                    @else
                                        <span class="text-muted">No Icon</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($type->features)
                                        @if (is_array($type->features))
                                            {{-- Features already cast to array --}}
                                            @foreach ($type->features as $feature)
                                                <span class="feature-badge">{{ $feature }}</span>
                                            @endforeach
                                        @elseif(is_string($type->features))
                                            {{-- If for some reason it's still a string --}}
                                            @php
                                                $featuresArray = json_decode($type->features, true);
                                            @endphp
                                            @if (is_array($featuresArray))
                                                @foreach ($featuresArray as $feature)
                                                    <span class="feature-badge">{{ $feature }}</span>
                                                @endforeach
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-muted">No features</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" type="checkbox"
                                            data-id="{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>{{ $type->display_order }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('user-types.edit', $type->id) }}" class="btn btn-sm btn-warning"
                                            title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        {{-- <form action="{{ route('user-types.destroy', $type->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this user type?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form> --}}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>No user types found</p>
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
                    url: `/user-types/${typeId}/toggle-status`,
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
