@extends('Website.Layout.master')

@section('custom_css')
    <style>
        .subscription-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
        }

        .subscription-card h3 {
            color: white;
            font-weight: 600;
        }

        .feature-item {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .info-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .badge {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid mt-4">
        <div id="table-section" class="table-container">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="section-title">
                        <i class="fas fa-receipt me-2"></i>
                        Subscription Details
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('subscriptions.index') }}">
                                    <i class="fas fa-receipt"></i> Subscriptions
                                </a>
                            </li>
                            <li class="breadcrumb-item active">Subscription #{{ $subscription->id }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="{{ route('users.show', $subscription->user_id) }}" class="btn btn-primary">
                        <i class="fas fa-user"></i> View User Profile
                    </a>
                </div>
            </div>

            <!-- Subscription Main Card -->
            <div class="subscription-card mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <h3 class="mb-3">
                            <i class="fas fa-crown me-2"></i>
                            {{ $subscription->plan_name }}
                        </h3>

                        <div class="row">
                            <div class="col-md-4">
                                <h5>User Information</h5>
                                <p><strong>Name:</strong> {{ $subscription->user->name ?? 'N/A' }}</p>
                                <p><strong>Email:</strong> {{ $subscription->user->email ?? 'N/A' }}</p>
                                <p><strong>Contact:</strong> {{ $subscription->user->contact_number ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <h5>Subscription Info</h5>
                                <p><strong>💰 Price Paid:</strong> ${{ number_format($subscription->price_paid, 2) }}</p>
                                <p><strong>📅 Duration:</strong> {{ ucfirst($subscription->duration_type) }}</p>
                                <p><strong>📊 Status:</strong>
                                    @if ($subscription->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @elseif($subscription->isCancelled())
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @elseif($subscription->isExpired())
                                        <span class="badge bg-danger">Expired</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h5>Timeline</h5>
                                <p><strong>🚀 Started:</strong> {{ $subscription->formatted_starts_at }}</p>
                                <p><strong>⏰ Expires:</strong> {{ $subscription->formatted_expires_at }}</p>
                                <p><strong>⏳ Remaining:</strong>
                                    @if ($subscription->days_remaining === 'Unlimited')
                                        <span class="badge bg-warning text-dark">Lifetime</span>
                                    @else
                                        <span class="badge bg-light text-dark">{{ $subscription->days_remaining }}
                                            days</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="info-box text-center">
                            <h6>Subscription ID</h6>
                            <h2 class="text-primary">#{{ $subscription->id }}</h2>
                            <hr>
                            <p class="text-muted mb-2">
                                <small>Created: {{ $subscription->created_at->format('d M Y, h:i A') }}</small>
                            </p>

                            @if ($subscription->is_active)
                                <form action="{{ route('subscriptions.cancel', $subscription->id) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm w-100">
                                        <i class="fas fa-times-circle"></i> Cancel Subscription
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($subscription->selected_features)
                    <hr style="border-color: rgba(255,255,255,0.3);" class="my-4">
                    <h5><i class="fas fa-check-double me-2"></i>Selected Features</h5>
                    <div class="row mt-3">
                        @php
                            $features = is_string($subscription->selected_features)
                                ? json_decode($subscription->selected_features, true)
                                : $subscription->selected_features;
                        @endphp
                        @if (is_array($features))
                            @foreach ($features as $feature)
                                <div class="col-md-6">
                                    <div class="feature-item">
                                        <i class="fas fa-check-circle me-2"></i>{{ $feature }}
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>

            <!-- User Full Details Card -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i> User Details</h5>
                </div>
                <div class="card-body">
                    @if ($subscription->user)
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>User ID:</strong> {{ $subscription->user->id }}</p>
                                <p><strong>Name:</strong> {{ $subscription->user->name ?? 'N/A' }}</p>
                                <p><strong>Contact:</strong> {{ $subscription->user->contact_number }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Email:</strong> {{ $subscription->user->email ?? 'N/A' }}</p>
                                <p><strong>DOB:</strong> {{ $subscription->user->dob ?? 'N/A' }}</p>
                                <p><strong>Gender:</strong> {{ ucfirst($subscription->user->gender ?? 'N/A') }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>City:</strong> {{ $subscription->user->city ?? 'N/A' }}</p>
                                <p><strong>State:</strong> {{ $subscription->user->state ?? 'N/A' }}</p>
                                <p><strong>Country:</strong> {{ $subscription->user->country ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Verified:</strong>
                                    @if ($subscription->user->is_verified)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-danger">No</span>
                                    @endif
                                </p>
                                <p><strong>Completed:</strong>
                                    @if ($subscription->user->is_completed)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-warning">No</span>
                                    @endif
                                </p>
                                <p><strong>Total Logins:</strong> {{ $subscription->user->login_count ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="text-end mt-3">
                            <a href="{{ route('users.show', $subscription->user->id) }}" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Full Profile
                            </a>
                        </div>
                    @else
                        <p class="text-muted">User information not available</p>
                    @endif
                </div>
            </div>

            <!-- Plan Details Card -->
            @if ($subscription->plan)
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-crown me-2"></i> Plan Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Plan ID:</strong> {{ $subscription->plan->id }}</p>
                                <p><strong>Plan Name:</strong> {{ $subscription->plan->name }}</p>
                                <p><strong>Original Price:</strong> ${{ number_format($subscription->plan->price, 2) }}</p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Duration:</strong> {{ ucfirst($subscription->plan->duration_type) }}</p>
                                <p><strong>Popular:</strong>
                                    @if ($subscription->plan->is_popular)
                                        <span class="badge bg-warning">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </p>
                                <p><strong>Active:</strong>
                                    @if ($subscription->plan->is_active)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-danger">No</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Description:</strong></p>
                                <p class="text-muted">{{ $subscription->plan->description ?? 'No description available' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Timeline Card -->
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Timeline & History</h5>
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
                                <td>{{ $subscription->formatted_starts_at }}</td>
                            </tr>
                            <tr>
                                <th>Expiry Date</th>
                                <td>{{ $subscription->formatted_expires_at }}</td>
                            </tr>
                            <tr>
                                <th>Days Remaining</th>
                                <td>
                                    @if ($subscription->days_remaining === 'Unlimited')
                                        <span class="badge bg-warning text-dark">Lifetime / Unlimited</span>
                                    @elseif($subscription->days_remaining > 30)
                                        <span class="badge bg-success">{{ $subscription->days_remaining }} days</span>
                                    @elseif($subscription->days_remaining > 0)
                                        <span class="badge bg-warning text-dark">{{ $subscription->days_remaining }}
                                            days</span>
                                    @else
                                        <span class="badge bg-danger">Expired</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Current Status</th>
                                <td>
                                    @if ($subscription->is_active)
                                        <span class="badge bg-success">✓ Active & Running</span>
                                    @elseif($subscription->isCancelled())
                                        <span class="badge bg-secondary">✗ Cancelled</span>
                                    @elseif($subscription->isExpired())
                                        <span class="badge bg-danger">⏰ Expired</span>
                                    @else
                                        <span class="badge bg-warning">⏳ Pending</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="text-center mb-4 mt-3">
            <a href="{{ route('subscriptions.index') }}" class="btn btn-lg btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to All Subscriptions
            </a>
            <a href="{{ route('users.show', $subscription->user_id) }}" class="btn btn-lg btn-primary">
                <i class="fas fa-user"></i> View User Profile
            </a>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        $(document).ready(function() {
            // Smooth scroll to top
            $('html, body').animate({
                scrollTop: 0
            }, 'fast');
        });
    </script>
@endsection
