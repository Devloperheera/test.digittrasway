@extends('Website.Layout.master')

@section('custom_css')
<style>
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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

    .user-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .user-link:hover {
        text-decoration: underline;
        color: #764ba2;
    }
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-receipt me-2"></i>
                All Subscriptions
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Plan</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Start Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $sub)
                        <tr>
                            <td>#{{ $sub->id }}</td>
                            <td>
                                <a href="{{ route('users.show', $sub->user_id) }}" class="user-link">
                                    <i class="fas fa-user-circle me-1"></i>
                                    {{ $sub->user->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td><strong>{{ $sub->plan_name }}</strong></td>
                            <td>${{ number_format($sub->price_paid, 2) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($sub->duration_type) }}</span></td>
                            <td>{{ $sub->starts_at ? $sub->starts_at->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $sub->expires_at ? $sub->expires_at->format('d M Y') : 'N/A' }}</td>
                            <td>
                                @if($sub->is_active)
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Active
                                    </span>
                                @elseif($sub->isCancelled())
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-times-circle me-1"></i>Cancelled
                                    </span>
                                @elseif($sub->isExpired())
                                    <span class="badge bg-danger">
                                        <i class="fas fa-exclamation-circle me-1"></i>Expired
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('subscriptions.show', $sub->id) }}"
                                   class="btn btn-sm btn-info"
                                   title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">
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
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@endsection
