@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .badge {
            padding: 5px 10px;
            font-size: 12px;
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

        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            display: inline-block;
            border: 1px solid #ddd;
        }

        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .section-title {
            color: #265b6b;
            font-weight: 700;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="table-section" class="table-container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-crown me-2"></i>
                        Vendor Plans Management
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('vendor-plans.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Plan
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
            <form method="GET" action="{{ route('vendor-plans.index') }}">
                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Search by name, description..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Duration</label>
                        <select name="duration_type" class="form-select">
                            <option value="">All Durations</option>
                            <option value="daily" {{ request('duration_type') == 'daily' ? 'selected' : '' }}>
                                Daily
                            </option>
                            <option value="weekly" {{ request('duration_type') == 'weekly' ? 'selected' : '' }}>
                                Weekly
                            </option>
                            <option value="monthly" {{ request('duration_type') == 'monthly' ? 'selected' : '' }}>
                                Monthly
                            </option>
                            <option value="quarterly" {{ request('duration_type') == 'quarterly' ? 'selected' : '' }}>
                                Quarterly
                            </option>
                            <option value="half_yearly" {{ request('duration_type') == 'half_yearly' ? 'selected' : '' }}>
                                Half Yearly
                            </option>
                            <option value="yearly" {{ request('duration_type') == 'yearly' ? 'selected' : '' }}>
                                Yearly
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="is_active" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><i class="fas fa-star"></i> Popular</label>
                        <select name="is_popular" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('is_popular') == '1' ? 'selected' : '' }}>Popular</option>
                            <option value="0" {{ request('is_popular') == '0' ? 'selected' : '' }}>Regular</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('vendor-plans.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 15%;">Name</th>
                            <th style="width: 10%;">Price</th>
                            <th style="width: 10%;">Duration</th>
                            <th style="width: 20%;">Features</th>
                            <th style="width: 8%;" class="text-center">Popular</th>
                            <th style="width: 8%;" class="text-center">Status</th>
                            <th style="width: 12%;">Button</th>
                            <th style="width: 7%;" class="text-center">Order</th>
                            <th style="width: 10%;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td class="text-center">#{{ $plan->id }}</td>
                                <td><strong>{{ $plan->name }}</strong>
                                    @if($plan->description)
                                    <br><small class="text-muted">{{ Str::limit($plan->description, 40) }}</small>
                                    @endif
                                </td>
                                <td><strong class="text-success fs-6">₹{{ number_format($plan->price, 2) }}</strong></td>
                                <td>
                                    @php
                                        $durationBadges = [
                                            'daily' => ['class' => 'bg-info', 'icon' => 'fa-calendar-day', 'text' => 'Daily'],
                                            'weekly' => ['class' => 'bg-primary', 'icon' => 'fa-calendar-week', 'text' => 'Weekly'],
                                            'monthly' => ['class' => 'bg-success', 'icon' => 'fa-calendar-alt', 'text' => 'Monthly'],
                                            'quarterly' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-calendar', 'text' => 'Quarterly'],
                                            'half_yearly' => ['class' => 'bg-orange text-white', 'icon' => 'fa-calendar-check', 'text' => 'Half Yearly'],
                                            'yearly' => ['class' => 'bg-danger', 'icon' => 'fa-calendar-alt', 'text' => 'Yearly']
                                        ];
                                        $badge = $durationBadges[$plan->duration_type] ?? ['class' => 'bg-secondary', 'icon' => 'fa-calendar', 'text' => ucfirst($plan->duration_type)];
                                    @endphp
                                    <span class="badge {{ $badge['class'] }}">
                                        <i class="fas {{ $badge['icon'] }}"></i> {{ $badge['text'] }}
                                    </span>
                                    <br>
                                    <small class="text-muted">{{ $plan->duration_days }} days</small>
                                </td>
                                <td>
                                    @if ($plan->features && is_array($plan->features) && count($plan->features) > 0)
                                        @foreach (array_slice($plan->features, 0, 2) as $feature)
                                            <span class="feature-badge">
                                                <i class="fas fa-check-circle"></i> {{ Str::limit($feature, 20) }}
                                            </span>
                                        @endforeach
                                        @if (count($plan->features) > 2)
                                            <span class="badge bg-secondary">+{{ count($plan->features) - 2 }} more</span>
                                        @endif
                                    @else
                                        <span class="text-muted"><i>No features</i></span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input popular-toggle" type="checkbox"
                                            data-id="{{ $plan->id }}"
                                            {{ $plan->is_popular ? 'checked' : '' }}
                                            style="cursor: pointer;">
                                    </div>
                                    @if($plan->is_popular)
                                    <span class="badge bg-warning text-dark mt-1">
                                        <i class="fas fa-star"></i> Popular
                                    </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input status-toggle" type="checkbox"
                                            data-id="{{ $plan->id }}"
                                            {{ $plan->is_active ? 'checked' : '' }}
                                            style="cursor: pointer;">
                                    </div>
                                    <span class="badge bg-{{ $plan->is_active ? 'success' : 'danger' }} mt-1">
                                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="color-preview"
                                            style="background-color: {{ $plan->button_color }};"></span>
                                        <small><strong>{{ $plan->button_text }}</strong></small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary fs-6">{{ $plan->sort_order }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('vendor-plans.edit', $plan->id) }}"
                                            class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('vendor-plans.destroy', $plan->id) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Are you sure you want to delete this plan?');">
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
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-4x mb-3 d-block"></i>
                                    <h5>No vendor plans found</h5>
                                    <p>Create your first vendor plan to get started!</p>
                                    <a href="{{ route('vendor-plans.create') }}" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus"></i> Add New Plan
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($plans->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    Showing <strong>{{ $plans->firstItem() ?? 0 }}</strong> to
                    <strong>{{ $plans->lastItem() ?? 0 }}</strong> of
                    <strong>{{ $plans->total() }}</strong> plans
                </div>
                <div>
                    {{ $plans->links('pagination::bootstrap-5') }}
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        $(document).ready(function() {
            // Status Toggle
            $('.status-toggle').on('change', function() {
                const planId = $(this).data('id');
                const isChecked = $(this).is(':checked');
                const toggleElement = $(this);

                $.ajax({
                    url: `/vendor-plans/${planId}/toggle-status`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        toggleElement.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'Status updated successfully');
                            // Reload page to update badge
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error('Failed to update status');
                            toggleElement.prop('checked', !isChecked);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error updating status');
                        toggleElement.prop('checked', !isChecked);
                    },
                    complete: function() {
                        toggleElement.prop('disabled', false);
                    }
                });
            });

            // Popular Toggle
            $('.popular-toggle').on('change', function() {
                const planId = $(this).data('id');
                const isChecked = $(this).is(':checked');
                const toggleElement = $(this);

                $.ajax({
                    url: `/vendor-plans/${planId}/toggle-popular`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        toggleElement.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'Popular status updated successfully');
                            // Reload page to update badge
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error('Failed to update popular status');
                            toggleElement.prop('checked', !isChecked);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error updating popular status');
                        toggleElement.prop('checked', !isChecked);
                    },
                    complete: function() {
                        toggleElement.prop('disabled', false);
                    }
                });
            });

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>

    <style>
        .bg-orange {
            background-color: #FF9800 !important;
        }
    </style>
@endsection
