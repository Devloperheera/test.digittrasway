@extends('Website.Layout.master')

@section('custom_css')
<style>
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .card-header {
        border-radius: 10px 10px 0 0 !important;
        padding: 15px 20px;
    }

    .subscription-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
    }

    .subscription-card h3 {
        color: white;
        font-weight: 700;
    }

    .feature-item {
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    .feature-item:last-child {
        border-bottom: none;
    }

    .info-row {
        border-bottom: 1px solid #eee;
        padding: 12px 0;
    }

    .info-row:last-child {
        border-bottom: none;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-crown me-2"></i>
                Subscription Details #{{ $subscription->id }}
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('vendor-subscriptions.index') }}">
                            <i class="fas fa-crown"></i> Subscriptions
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Subscription #{{ $subscription->id }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('vendor-subscriptions.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Subscription Summary Card -->
    <div class="subscription-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-3">
                    <i class="fas fa-crown me-2"></i>
                    {{ $subscription->plan_name }}
                </h3>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>💰 Price Paid:</strong> {{ $subscription->formatted_price }}</p>
                        <p><strong>📅 Duration:</strong> {{ $subscription->duration_text }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>🚀 Started:</strong> {{ $subscription->starts_at->format('d M Y') }}</p>
                        <p><strong>⏰ Expires:</strong> {{ $subscription->expires_at->format('d M Y') }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>⏳ Remaining:</strong>
                            @if($subscription->isActive())
                                <span class="badge bg-light text-dark">{{ $subscription->remaining_days }} days</span>
                            @else
                                <span class="badge bg-danger">Expired</span>
                            @endif
                        </p>
                        <p><strong>📊 Status:</strong> {!! $subscription->status_badge !!}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-white rounded p-3 text-dark">
                    <h6>Subscription ID</h6>
                    <h2 class="text-primary">#{{ $subscription->id }}</h2>
                    <small>Created: {{ $subscription->created_at->format('d M Y') }}</small>

                    @if($subscription->status === 'active')
                    <hr>
                    <form action="{{ route('vendor-subscriptions.cancel', $subscription->id) }}"
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger w-100">
                            <i class="fas fa-times-circle"></i> Cancel Subscription
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        @if($subscription->plan_features && is_array($subscription->plan_features) && count($subscription->plan_features) > 0)
        <hr style="border-color: rgba(255,255,255,0.3);" class="my-4">
        <h5><i class="fas fa-check-double me-2"></i>Plan Features</h5>
        <div class="row mt-3">
            @foreach($subscription->plan_features as $feature)
            <div class="col-md-6">
                <div class="feature-item">
                    <i class="fas fa-check-circle me-2"></i>{{ $feature }}
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Vendor Details -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i> Vendor Details</h5>
        </div>
        <div class="card-body">
            @if($subscription->vendor)
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Name:</strong> {{ $subscription->vendor->name }}
                    </div>
                    <div class="info-row">
                        <strong>Email:</strong> {{ $subscription->vendor->email ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Contact:</strong> {{ $subscription->vendor->contact_number }}
                    </div>
                    <div class="info-row">
                        <strong>City:</strong> {{ $subscription->vendor->city ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Verified:</strong>
                        @if($subscription->vendor->is_verified)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-danger">No</span>
                        @endif
                    </div>
                    <div class="info-row">
                        <a href="{{ route('vendors.show', $subscription->vendor->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
            @else
            <p class="text-muted">Vendor information not available</p>
            @endif
        </div>
    </div>

    <!-- Plan Details -->
    @if($subscription->vendorPlan)
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-crown me-2"></i> Original Plan Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Plan Name:</strong> {{ $subscription->vendorPlan->name }}
                    </div>
                    <div class="info-row">
                        <strong>Plan Price:</strong> {{ $subscription->vendorPlan->formatted_price }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Duration:</strong> {{ $subscription->vendorPlan->duration_text }}
                    </div>
                    <div class="info-row">
                        <strong>Duration Days:</strong> {{ $subscription->vendorPlan->duration_days }} days
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Popular:</strong>
                        @if($subscription->vendorPlan->is_popular)
                            <span class="badge bg-warning text-dark">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                    <div class="info-row">
                        <strong>Active:</strong>
                        @if($subscription->vendorPlan->is_active)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-danger">No</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Details -->
    @if($subscription->payment)
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i> Payment Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Payment ID:</strong>
                        <a href="{{ route('vendor-payments.show', $subscription->payment->id) }}" class="text-primary">
                            #{{ $subscription->payment->id }}
                        </a>
                    </div>
                    <div class="info-row">
                        <strong>Razorpay Order ID:</strong>
                        <code>{{ $subscription->payment->razorpay_order_id ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>Payment Status:</strong> {!! $subscription->payment->status_badge !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Amount:</strong> {{ $subscription->payment->formatted_amount }}
                    </div>
                    <div class="info-row">
                        <strong>Payment Method:</strong>
                        {!! $subscription->payment->payment_method_icon !!}
                        {{ ucfirst($subscription->payment->payment_method ?? 'N/A') }}
                    </div>
                    <div class="info-row">
                        <strong>Paid At:</strong>
                        {{ $subscription->payment->paid_at ? $subscription->payment->paid_at->format('d M Y, h:i A') : 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Timeline -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Timeline</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Subscription Created</th>
                        <td>{{ $subscription->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    <tr>
                        <th>Last Updated</th>
                        <td>{{ $subscription->updated_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td>{{ $subscription->starts_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    <tr>
                        <th>Expiry Date</th>
                        <td>{{ $subscription->expires_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    <tr>
                        <th>Days Remaining</th>
                        <td>
                            @if($subscription->isActive())
                                <span class="badge bg-success">{{ $subscription->remaining_days }} days left</span>
                            @else
                                <span class="badge bg-danger">Expired</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="text-center mb-4 mt-3">
        <a href="{{ route('vendor-subscriptions.index') }}" class="btn btn-lg btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Subscriptions List
        </a>
    </div>
</div>
@endsection

@section('custom_js')
@endsection
