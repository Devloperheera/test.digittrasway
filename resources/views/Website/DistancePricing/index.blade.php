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

    .price-badge {
        background: #e3f2fd;
        color: #1976d2;
        padding: 8px 12px;
        border-radius: 5px;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-route me-2"></i>
                Distance Pricing Management
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('distance-pricings.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Pricing
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="60">ID</th>
                            <th>Name</th>
                            <th>Price/KM</th>
                            <th>Distance Range</th>
                            <th>Min Charge</th>
                            <th>Description</th>
                            <th width="80">Status</th>
                            <th width="80">Order</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pricings as $pricing)
                        <tr>
                            <td>{{ $pricing->id }}</td>
                            <td><strong>{{ $pricing->name }}</strong></td>
                            <td>
                                <span class="price-badge">
                                    ₹{{ number_format($pricing->price_per_km, 2) }}/km
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    {{ $pricing->distance_range }}
                                </span>
                            </td>
                            <td>
                                <strong>₹{{ number_format($pricing->minimum_charge, 2) }}</strong>
                            </td>
                            <td>{{ $pricing->description ?? 'N/A' }}</td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle"
                                           type="checkbox"
                                           data-id="{{ $pricing->id }}"
                                           {{ $pricing->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $pricing->sort_order }}</span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('distance-pricings.edit', $pricing->id) }}"
                                       class="btn btn-sm btn-warning"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('distance-pricings.destroy', $pricing->id) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this pricing?');">
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
                            <td colspan="9" class="text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No distance pricings found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Price Calculator Card -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Price Calculator</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label for="distance" class="form-label">Enter Distance (KM)</label>
                    <input type="number"
                           class="form-control"
                           id="distance"
                           placeholder="Enter distance in KM"
                           min="0"
                           step="0.1">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" class="btn btn-success w-100" id="calculateBtn">
                        <i class="fas fa-calculator"></i> Calculate Price
                    </button>
                </div>
            </div>
            <div id="priceResult" class="mt-3"></div>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
<script>
$(document).ready(function() {
    // Status Toggle
    $('.status-toggle').on('change', function() {
        const pricingId = $(this).data('id');
        const isChecked = $(this).is(':checked');
        const toggleElement = $(this);

        $.ajax({
            url: `/distance-pricings/${pricingId}/toggle-status`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                toggleElement.prop('disabled', true);
            },
            success: function(response) {
                if(response.success) {
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

    // Price Calculator
    $('#calculateBtn').on('click', function() {
        const distance = $('#distance').val();

        if(!distance || distance <= 0) {
            toastr.warning('Please enter a valid distance');
            return;
        }

        $.ajax({
            url: '/distance-pricings/calculate-price',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                distance: distance
            },
            beforeSend: function() {
                $('#calculateBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calculating...');
            },
            success: function(response) {
                if(response.success) {
                    $('#priceResult').html(`
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Price Calculated!</h5>
                            <hr>
                            <p><strong>Pricing Type:</strong> ${response.pricing_name}</p>
                            <p><strong>Distance:</strong> ${response.distance} KM</p>
                            <p><strong>Price per KM:</strong> ₹${response.price_per_km}</p>
                            <p><strong>Minimum Charge:</strong> ₹${response.minimum_charge}</p>
                            <h4 class="text-success"><strong>Total Price: ₹${response.calculated_price}</strong></h4>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to calculate price';
                $('#priceResult').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> ${message}
                    </div>
                `);
            },
            complete: function() {
                $('#calculateBtn').prop('disabled', false).html('<i class="fas fa-calculator"></i> Calculate Price');
            }
        });
    });
});
</script>
@endsection
