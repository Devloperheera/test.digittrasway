@extends('Website.Layout.master')

@section('custom_css')
<style>
.dashboard-header{background:linear-gradient(135deg,#265b6b 0%,#1a4350 100%);color:#fff;padding:25px 30px;border-radius:15px;margin-bottom:30px;box-shadow:0 4px 15px rgba(38,91,107,.3)}
.stat-card{border-radius:15px;padding:25px;transition:all .3s ease;border:none;box-shadow:0 2px 10px rgba(0,0,0,.08);height:100%}
.stat-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,.15)}
.stat-card .icon-box{width:70px;height:70px;border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:15px}
.stat-card h2{font-size:32px;font-weight:700;margin:10px 0}
.stat-card h5{margin:0;font-size:16px;opacity:.9}
.chart-container{background:#fff;border-radius:15px;padding:25px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:30px}
.table-container{background:#fff;border-radius:15px;padding:25px;box-shadow:0 2px 10px rgba(0,0,0,.08)}
.quick-stat-card{background:#fff;border-radius:15px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.08);transition:all .3s ease}
.quick-stat-card:hover{transform:translateY(-3px);box-shadow:0 5px 15px rgba(0,0,0,.12)}
</style>
@endsection

@section('content')
<div class="content-area">
    <div class="container-fluid">

        {{-- Header --}}
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 style="margin:0;font-weight:700">Dashboard</h1>
                    <p style="margin:5px 0 0;opacity:.9">Welcome back, <strong>Admin</strong></p>
                </div>
                <div style="background:rgba(255,255,255,.2);padding:10px 20px;border-radius:25px;font-weight:600">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <span id="currentDate"></span>
                </div>
            </div>
        </div>

        {{-- Main Stats --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('users.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff">
                        <div class="icon-box" style="background:rgba(255,255,255,.2)">
                            <i class="fas fa-users"></i>
                        </div>
                        <h2>{{ $totalUsers }}</h2>
                        <h5>Total Users</h5>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('vendors.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff">
                        <div class="icon-box" style="background:rgba(255,255,255,.2)">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h2>{{ $totalVendors }}</h2>
                        <h5>Total Vendors</h5>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('booking-requests.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);color:#fff">
                        <div class="icon-box" style="background:rgba(255,255,255,.2)">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h2>{{ $totalBookings }}</h2>
                        <h5>Booking Requests</h5>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('plans.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:linear-gradient(135deg,#fa709a 0%,#fee140 100%);color:#fff">
                        <div class="icon-box" style="background:rgba(255,255,255,.2)">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h2>{{ $totalPlans }}</h2>
                        <h5>Active Plans</h5>
                    </div>
                </a>
            </div>
        </div>

        {{-- Secondary Stats --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('vendor-vehicles.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:#265b6b;color:#fff">
                        <div class="icon-box" style="background:rgba(255,255,255,.2)">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h2>0</h2>
                        <h5>Vendor Vehicles</h5>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('vendor-plans.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:linear-gradient(135deg,#30cfd0 0%,#330867 100%);color:#fff">
                        <div class="icon-box" style="background:rgba(255,255,255,.2)">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h2>{{ $totalVendorPlans }}</h2>
                        <h5>Vendor Plans</h5>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('vendor-payments.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:linear-gradient(135deg,#a8edea 0%,#fed6e3 100%);color:#333">
                        <div class="icon-box" style="background:rgba(38,91,107,.1)">
                            <i class="fas fa-money-bill-wave" style="color:#265b6b"></i>
                        </div>
                        <h2>₹{{ number_format($totalRevenue) }}</h2>
                        <h5>Total Revenue</h5>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <a href="{{ route('document-verification.index') }}" style="text-decoration:none">
                    <div class="stat-card" style="background:linear-gradient(135deg,#ffecd2 0%,#fcb69f 100%);color:#333">
                        <div class="icon-box" style="background:rgba(38,91,107,.1)">
                            <i class="fas fa-search-plus" style="color:#265b6b"></i>
                        </div>
                        <h2><i class="fas fa-check-circle" style="font-size:40px;color:#28a745"></i></h2>
                        <h5>Doc Verification</h5>
                    </div>
                </a>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="quick-stat-card" style="border-left:4px solid #667eea">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">User Types</p>
                            <h3 style="font-weight:700;margin:0">{{ $totalUserTypes }}</h3>
                        </div>
                        <i class="fas fa-user-tag fa-2x" style="color:#667eea"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="quick-stat-card" style="border-left:4px solid #f093fb">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Truck Types</p>
                            <h3 style="font-weight:700;margin:0">{{ $totalTruckTypes }}</h3>
                        </div>
                        <i class="fas fa-truck fa-2x" style="color:#f093fb"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="quick-stat-card" style="border-left:4px solid #4facfe">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Vehicle Models</p>
                            <h3 style="font-weight:700;margin:0">{{ $totalVehicleModels }}</h3>
                        </div>
                        <i class="fas fa-car fa-2x" style="color:#4facfe"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="quick-stat-card" style="border-left:4px solid #fa709a">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Subscriptions</p>
                            <h3 style="font-weight:700;margin:0">{{ $activeSubscriptions }}</h3>
                        </div>
                        <i class="fas fa-crown fa-2x" style="color:#fa709a"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart & Overview --}}
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="chart-container">
                    <h5 style="color:#265b6b;font-weight:600;margin-bottom:20px">
                        <i class="fas fa-chart-line me-2"></i>User Registration Trend (Last 7 Days)
                    </h5>
                    <canvas id="userChart" height="80"></canvas>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="chart-container">
                    <h5 style="color:#265b6b;font-weight:600;margin-bottom:20px">
                        <i class="fas fa-info-circle me-2"></i>System Overview
                    </h5>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between border-0 px-0">
                            <span><i class="fas fa-users text-primary me-2"></i>Total Users</span>
                            <strong>{{ $totalUsers }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between border-0 px-0">
                            <span><i class="fas fa-user-tie text-success me-2"></i>Total Vendors</span>
                            <strong>{{ $totalVendors }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between border-0 px-0">
                            <span><i class="fas fa-crown text-warning me-2"></i>Active Plans</span>
                            <strong>{{ $totalPlans }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between border-0 px-0">
                            <span><i class="fas fa-truck text-info me-2"></i>Truck Types</span>
                            <strong>{{ $totalTruckTypes }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between border-0 px-0">
                            <span><i class="fas fa-car text-danger me-2"></i>Vehicle Models</span>
                            <strong>{{ $totalVehicleModels }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tables --}}
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 style="color:#265b6b;font-weight:600;margin:0">
                            <i class="fas fa-users me-2"></i>Recent Users
                        </h5>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead style="background:#f8f9fa">
                                <tr><th>Name</th><th>Email</th><th>Joined</th></tr>
                            </thead>
                            <tbody>
                                @forelse($recentUsers as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->created_at->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">No users yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 style="color:#265b6b;font-weight:600;margin:0">
                            <i class="fas fa-user-tie me-2"></i>Recent Vendors
                        </h5>
                        <a href="{{ route('vendors.index') }}" class="btn btn-sm btn-success">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead style="background:#f8f9fa">
                                <tr><th>Name</th><th>Mobile</th><th>Joined</th></tr>
                            </thead>
                            <tbody>
                                @forelse($recentVendors as $vendor)
                                <tr>
                                    <td>{{ $vendor->name }}</td>
                                    <td>{{ $vendor->mobile }}</td>
                                    <td>{{ $vendor->created_at->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">No vendors yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('custom_js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.getElementById('currentDate').textContent=new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
const ctx=document.getElementById('userChart');
if(ctx){new Chart(ctx,{type:'line',data:{labels:{!!json_encode($chartData['labels'])!!},datasets:[{label:'User Registrations',data:{!!json_encode($chartData['data'])!!},borderColor:'#667eea',backgroundColor:'rgba(102,126,234,0.1)',tension:0.4,fill:true,borderWidth:3,pointRadius:5,pointBackgroundColor:'#667eea'}]},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:true,position:'top'}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}})}
</script>
@endsection
