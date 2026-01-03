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
    
    .payment-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
    }
    
    .payment-card h3 {
        color: white;
        font-weight: 700;
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
                <i class="fas fa-receipt me-2"></i>
                Payment Details #{{ $payment->id }}
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('vendor-payments.index') }}">
                            <i class="fas fa-money-bill-wave"></i> Payments
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Payment #{{ $payment->id }}</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('vendor-payments.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>

    <!-- Payment Summary Card -->
    <div class="payment-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-3">
                    <i class="fas fa-money-check-alt me-2"></i>
                    Payment Summary
                </h3>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>💰 Amount:</strong> {{ $payment->formatted_amount }}</p>
                        <p><strong>📊 Status:</strong> {!! $payment->status_badge !!}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>💳 Method:</strong> {!! $payment->payment_method_icon !!} {{ ucfirst($payment->payment_method ?? 'N/A') }}</p>
                        <p><strong>💵 Currency:</strong> {{ strtoupper($payment->currency) }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>📅 Date:</strong> {{ $payment->created_at->format('d M Y') }}</p>
                        <p><strong>⏰ Time:</strong> {{ $payment->created_at->format('h:i A') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="bg-white rounded p-3 text-dark">
                    <h6>Receipt Number</h6>
                    <h3 class="text-primary">{{ $payment->receipt_number ?? 'N/A' }}</h3>
                    @if($payment->paid_at)
                        <small>Paid at: {{ $payment->paid_at->format('d M Y, h:i A') }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor Details -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user me-2"></i> Vendor Details</h5>
        </div>
        <div class="card-body">
            @if($payment->vendor)
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Name:</strong> {{ $payment->vendor->name }}
                    </div>
                    <div class="info-row">
                        <strong>Email:</strong> {{ $payment->vendor->email ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Contact:</strong> {{ $payment->vendor->contact_number }}
                    </div>
                    <div class="info-row">
                        <strong>City:</strong> {{ $payment->vendor->city ?? 'N/A' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Verified:</strong> 
                        @if($payment->vendor->is_verified)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-danger">No</span>
                        @endif
                    </div>
                    <div class="info-row">
                        <a href="{{ route('vendors.show', $payment->vendor->id) }}" class="btn btn-sm btn-info">
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
    @if($payment->vendorPlan)
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-crown me-2"></i> Plan Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Plan Name:</strong> {{ $payment->vendorPlan->name }}
                    </div>
                    <div class="info-row">
                        <strong>Duration:</strong> {{ $payment->vendorPlan->duration_text }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Plan Price:</strong> {{ $payment->vendorPlan->formatted_price }}
                    </div>
                    <div class="info-row">
                        <strong>Amount Paid:</strong> ₹{{ number_format($payment->amount_paid, 2) }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <strong>Description:</strong> {{ $payment->vendorPlan->description ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Razorpay Transaction Details -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i> Transaction Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <strong>Razorpay Order ID:</strong> 
                        <code>{{ $payment->razorpay_order_id ?? 'N/A' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>Razorpay Payment ID:</strong> 
                        <code>{{ $payment->razorpay_payment_id ?? 'Pending' }}</code>
                    </div>
                    <div class="info-row">
                        <strong>Payment Status:</strong> {!! $payment->status_badge !!}
                    </div>
                    <div class="info-row">
                        <strong>Order Status:</strong> 
                        <span class="badge bg-secondary">{{ $payment->order_status ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    @if($payment->payment_method == 'card' && $payment->card_id)
                        <div class="info-row">
                            <strong>Card ID:</strong> {{ $payment->card_id }}
                        </div>
                    @endif
                    @if($payment->payment_method == 'netbanking' && $payment->bank)
                        <div class="info-row">
                            <strong>Bank:</strong> {{ $payment->bank }}
                        </div>
                    @endif
                    @if($payment->payment_method == 'wallet' && $payment->wallet)
                        <div class="info-row">
                            <strong>Wallet:</strong> {{ $payment->wallet }}
                        </div>
                    @endif
                    @if($payment->payment_method == 'upi' && $payment->vpa)
                        <div class="info-row">
                            <strong>UPI ID:</strong> {{ $payment->vpa }}
                        </div>
                    @endif
                    <div class="info-row">
                        <strong>Contact Email:</strong> {{ $payment->email ?? 'N/A' }}
                    </div>
                    <div class="info-row">
                        <strong>Contact Number:</strong> {{ $payment->contact ?? 'N/A' }}
                    </div>
                </div>
            </div>

            @if($payment->error_code || $payment->error_description)
            <hr>
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Error Details</h6>
                @if($payment->error_code)
                    <p><strong>Error Code:</strong> {{ $payment->error_code }}</p>
                @endif
                @if($payment->error_description)
                    <p><strong>Error Description:</strong> {{ $payment->error_description }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Razorpay Response (if available) -->
    @if($payment->razorpay_response && is_array($payment->razorpay_response))
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-code me-2"></i> Raw Response Data</h5>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded"><code>{{ json_encode($payment->razorpay_response, JSON_PRETTY_PRINT) }}</code></pre>
        </div>
    </div>
    @endif

    <!-- Back Button -->
    <div class="text-center mb-4 mt-3">
        <a href="{{ route('vendor-payments.index') }}" class="btn btn-lg btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Payments List
        </a>
    </div>
</div>
@endsection

@section('custom_js')
@endsection
