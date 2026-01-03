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

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stat-card h3 {
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="table-section" class="table-container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        Vendor Payments
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>₹{{ number_format($payments->where('payment_status', 'success')->sum('amount_paid'), 2) }}</h3>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <h3>{{ $payments->where('payment_status', 'success')->count() }}</h3>
                        <p class="mb-0">Successful</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3>{{ $payments->where('payment_status', 'failed')->count() }}</h3>
                        <p class="mb-0">Failed</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3>{{ $payments->where('payment_status', 'pending')->count() }}</h3>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('vendor-payments.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><i class="fas fa-search"></i> Search</label>
                                <input type="text" name="search" class="form-control"
                                    placeholder="Order ID, Payment ID, Email..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" class="form-select">
                                    <option value="">All</option>
                                    <option value="success" {{ request('payment_status') == 'success' ? 'selected' : '' }}>
                                        Success</option>
                                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>
                                        Pending</option>
                                    <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>
                                        Failed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="">All</option>
                                    <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card
                                    </option>
                                    <option value="netbanking"
                                        {{ request('payment_method') == 'netbanking' ? 'selected' : '' }}>Net Banking
                                    </option>
                                    <option value="wallet" {{ request('payment_method') == 'wallet' ? 'selected' : '' }}>
                                        Wallet</option>
                                    <option value="upi" {{ request('payment_method') == 'upi' ? 'selected' : '' }}>UPI
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Vendor</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Order ID</th>
                                    <th>Payment ID</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>#{{ $payment->id }}</td>
                                        <td>
                                            <a href="{{ route('vendors.show', $payment->vendor_id) }}"
                                                class="text-primary">
                                                {{ $payment->vendor->name ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td>{{ $payment->vendorPlan->name ?? 'N/A' }}</td>
                                        <td><strong>{{ $payment->formatted_amount }}</strong></td>
                                        <td>
                                            {!! $payment->payment_method_icon !!}
                                            {{ ucfirst($payment->payment_method ?? 'N/A') }}
                                        </td>
                                        <td><code>{{ $payment->razorpay_order_id ?? 'N/A' }}</code></td>
                                        <td><code>{{ $payment->razorpay_payment_id ?? 'Pending' }}</code></td>
                                        <td>{!! $payment->status_badge !!}</td>
                                        <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <a href="{{ route('vendor-payments.show', $payment->id) }}"
                                                class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No payments found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $payments->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
@endsection
