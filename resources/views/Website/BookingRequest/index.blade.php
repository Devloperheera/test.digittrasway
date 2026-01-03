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
</style>
@endsection

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="section-title">
                <i class="fas fa-clipboard-list me-2"></i>
                All Booking Requests
            </h2>
        </div>
    </div>

    <!-- Filters with Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('booking-requests.index') }}">
                <div class="row g-3">
                    <!-- Search Bar -->
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-search"></i> Search</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search by ID, Booking ID, Vendor..."
                               value="{{ request('search') }}">
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>

                    <!-- End Date -->
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('booking-requests.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
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
                            <th>Booking ID</th>
                            <th>Vendor</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Expires At</th>
                            <th>Responded At</th>
                            <th>Sequence</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookingRequests as $request)
                        <tr>
                            <td>#{{ $request->id }}</td>
                            <td>
                                <a href="#" class="text-primary">
                                    #{{ $request->booking_id }}
                                </a>
                            </td>
                            <td>
                                @if($request->vendor)
                                    <i class="fas fa-user-circle me-1"></i>
                                    {{ $request->vendor->name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{!! $request->status_badge !!}</td>
                            <td>{{ $request->sent_at ? $request->sent_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            <td>{{ $request->expires_at ? $request->expires_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            <td>{{ $request->responded_at ? $request->responded_at->format('d M Y, h:i A') : 'Not Responded' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $request->sequence_number }}</span>
                            </td>
                            <td>
                                <a href="{{ route('booking-requests.show', $request->id) }}"
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
                                <p>No booking requests found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination with Query String -->
            <div class="mt-3">
                {{ $bookingRequests->links('pagination::bootstrap-5') }}
            </div>

            <!-- Results Info -->
            <div class="text-muted">
                Showing {{ $bookingRequests->firstItem() ?? 0 }} to {{ $bookingRequests->lastItem() ?? 0 }}
                of {{ $bookingRequests->total() }} results
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
@endsection
