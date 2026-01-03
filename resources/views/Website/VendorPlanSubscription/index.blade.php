@extends('Website.Layout.master')

@section('custom_css')
<style>
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

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
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-crown me-2"></i>
                Vendor Plan Subscriptions
            </h2>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h3>{{ $subscriptions->where('status', 'active')->count() }}</h3>
                <p class="mb-0">Active Subscriptions</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3>{{ $subscriptions->where('status', 'expired')->count() }}</h3>
                <p class="mb-0">Expired</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3>{{ $subscriptions->where('status', 'cancelled')->count() }}</h3>
                <p class="mb-0">Cancelled</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h3>₹{{ number_format($subscriptions->where('status', 'active')->sum('price_paid'), 0) }}</h3>
                <p class="mb-0">Total Revenue</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('vendor-subscriptions.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="ID, Plan, Vendor..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Duration</label>
                        <select name="duration_type" class="form-select">
                            <option value="">All</option>
                            <option value="monthly" {{ request('duration_type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="yearly" {{ request('duration_type') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                            <option value="lifetime" {{ request('duration_type') == 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
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
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Start Date</th>
                            <th>Expiry Date</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                        <tr>
                            <td>#{{ $subscription->id }}</td>
                            <td>
                                <a href="{{ route('vendors.show', $subscription->vendor_id) }}" class="text-primary">
                                    {{ $subscription->vendor->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td><strong>{{ $subscription->plan_name }}</strong></td>
                            <td><strong>{{ $subscription->formatted_price }}</strong></td>
                            <td><span class="badge bg-info">{{ $subscription->duration_text }}</span></td>
                            <td>{{ $subscription->starts_at ? $subscription->starts_at->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $subscription->expires_at ? $subscription->expires_at->format('d M Y') : 'N/A' }}</td>
                            <td>
                                @if($subscription->isActive())
                                    <span class="badge bg-success">{{ $subscription->remaining_days }} days</span>
                                @else
                                    <span class="badge bg-danger">Expired</span>
                                @endif
                            </td>
                            <td>{!! $subscription->status_badge !!}</td>
                            <td>
                                <a href="{{ route('vendor-subscriptions.show', $subscription->id) }}"
                                   class="btn btn-sm btn-info"
                                   title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No subscriptions found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $subscriptions->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
@endsection
