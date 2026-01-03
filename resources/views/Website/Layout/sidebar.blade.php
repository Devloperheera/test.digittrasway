<div class="sidebar" id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-center">
        <a href="{{ route('Website.home') }}" class="text-decoration-none">
            <img src="{{ asset('web_assets/images/logo.png') }}" width="100px" alt="">
            {{-- <h1 class="text-decoration-none text-light text-uppercase" style="font-family: auto;"></h1> --}}
        </a>
    </div>


    <nav class="sidebar-menu">
        <a href="{{ route('Website.home') }}" class="menu-item active" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-tachometer-alt"></i>
                <span>My Dashboard</span>
            </div>
        </a>


        <a href="{{ route('users.index') }}" class="menu-item" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </div>
        </a>



        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-user-tag"></i>
                <span>User Types</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('user-types.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add User Type
                </a>
            </li>
            <li>
                <a href="{{ route('user-types.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View User Types
                </a>
            </li>
        </ul>


        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-truck"></i>
                <span>Truck Types</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('truck-types.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add Truck Type
                </a>
            </li>
            <li>
                <a href="{{ route('truck-types.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View Truck Types
                </a>
            </li>
        </ul>
        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-crown"></i>
                <span>Plans</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('plans.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add Plan
                </a>
            </li>
            <li>
                <a href="{{ route('plans.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View Plans
                </a>
            </li>
        </ul>


        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-receipt"></i>
                <span>Plans Subscriptions</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('subscriptions.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>All Subscriptions
                </a>
            </li>
            <li>
                <a href="{{ route('plans.index') }}" data-navigation="true">
                    <i class="fas fa-crown me-2"></i>Manage Plans
                </a>
            </li>
        </ul>




        <a href="{{ route('subscriptions.index') }}"
            class="menu-item {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-receipt"></i>
                <span>Plans Subscriptions</span>
            </div>
        </a>


        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-route"></i>
                <span>Distance Pricing</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('distance-pricings.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add Pricing
                </a>
            </li>
            <li>
                <a href="{{ route('distance-pricings.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View Pricings
                </a>
            </li>
        </ul>


        <a href="{{ route('booking-requests.index') }}"
            class="menu-item {{ request()->routeIs('booking-requests.*') ? 'active' : '' }}" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-clipboard-list"></i>
                <span>Booking Requests</span>
            </div>
        </a>


        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-truck-moving"></i>
                <span>Truck Specifications</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('truck-specifications.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add Specification
                </a>
            </li>
            <li>
                <a href="{{ route('truck-specifications.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View Specifications
                </a>
            </li>
        </ul>


        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-car"></i>
                <span>Vehicle Categories</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('vehicle-categories.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add Category
                </a>
            </li>
            <li>
                <a href="{{ route('vehicle-categories.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View Categories
                </a>
            </li>
        </ul>
        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-truck"></i>
                <span>Vehicle Models</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('vehicle-models.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add Model
                </a>
            </li>
            <li>
                <a href="{{ route('vehicle-models.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View Models
                </a>
            </li>
        </ul>
        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-crown"></i>
                <span>Vendor Plans</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('vendor-plans.create') }}" data-navigation="true">
                    <i class="fas fa-plus-circle me-2"></i>Add Plan
                </a>
            </li>
            <li>
                <a href="{{ route('vendor-plans.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>View Plans
                </a>
            </li>
        </ul>
        <a href="{{ route('vendor-payments.index') }}"
            class="menu-item {{ request()->routeIs('vendor-payments.*') ? 'active' : '' }}" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-money-bill-wave"></i>
                <span>Vendor Payments</span>
            </div>
        </a>
        <div class="menu-item" data-toggle="submenu">
            <div class="menu-item-content">
                <i class="fas fa-crown"></i>
                <span>Subscriptions</span>
            </div>
            <i class="fas fa-chevron-down menu-arrow"></i>
        </div>
        <ul class="submenu submenu-slide">
            <li>
                <a href="{{ route('vendor-subscriptions.index') }}" data-navigation="true">
                    <i class="fas fa-list me-2"></i>All Subscriptions
                </a>
            </li>
            <li>
                <a href="{{ route('vendor-plans.index') }}" data-navigation="true">
                    <i class="fas fa-crown me-2"></i>Manage Plans
                </a>
            </li>
            <li>
                <a href="{{ route('vendor-payments.index') }}" data-navigation="true">
                    <i class="fas fa-money-bill-wave me-2"></i>Payments
                </a>
            </li>
        </ul>
        <a href="{{ route('vendor-vehicles.index') }}"
            class="menu-item {{ request()->routeIs('vendor-vehicles.*') ? 'active' : '' }}" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-truck"></i>
                <span>Vendor Vehicles</span>
            </div>
        </a>
        <a href="{{ route('vendors.index') }}"
            class="menu-item {{ request()->routeIs('vendors.*') ? 'active' : '' }}" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-users"></i>
                <span>Vendors</span>
            </div>
        </a>

        <a href="{{ route('document-verification.index') }}"
            class="menu-item {{ request()->routeIs('document-verification.*') ? 'active' : '' }}">
            <i class="fas fa-search-plus"></i>
            <span>Document Verification</span>
        </a>
        <a href="{{ route('materials.index') }}"
            class="menu-item {{ request()->routeIs('materials.*') ? 'active' : '' }}" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-box-open me-2"></i>
                <span>Materials</span>
            </div>
        </a>

        <!-- Materials Menu Item -->


        {{-- Employee Management Menu --}}

        <a href="{{ route('employees.index') }}"
            class="menu-item {{ request()->routeIs('employees.*') ? 'active' : '' }}" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-users-cog"></i>
                <span>Employees</span>
            </div>
        </a>


        <a href="javascript:void(0)" class="menu-item" onclick="logout()">
            <div class="menu-item-content">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </div>
        </a>






        <!-- ✅ Forms Page Link -->
        {{-- <a href="{{ route('Website.forms') }}" class="menu-item" data-navigation="true">
            <div class="menu-item-content">
                <i class="fas fa-file-alt"></i>
                <span>Forms</span>
                </div>
            </a> --}}


        <!-- ✅ Table Page Link -->
        {{-- <a href="{{ route('Website.table') }}" class="menu-item" data-navigation="true">
                <div class="menu-item-content">
                    <i class="fas fa-table"></i>
                    <span>Tables</span>
                </div>
            </a> --}}




        <!-- ✅ Hidden logout form -->
        <form id="logoutForm" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </nav>
</div>


<!-- Meta CSRF token -->
<meta name="csrf-token" content="{{ csrf_token() }}">


<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.20/sweetalert2.all.min.js"></script>
<script>
    // ✅ Logout function with SweetAlert confirmation
    function logout() {
        Swal.fire({
            title: 'Logout Confirmation',
            text: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Logout!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            customClass: {
                confirmButton: 'btn btn-danger mx-2',
                cancelButton: 'btn btn-secondary mx-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Logging out...',
                    text: 'Please wait while we log you out',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });


                // Submit logout form after short delay
                setTimeout(() => {
                    document.getElementById('logoutForm').submit();
                }, 1500);
            }
        });
    }


    // ✅ Active menu highlighting
    document.addEventListener('DOMContentLoaded', function() {
        const currentUrl = window.location.href;
        const menuItems = document.querySelectorAll('.menu-item[data-navigation="true"]');


        // Remove active class from all items first
        menuItems.forEach(item => {
            item.classList.remove('active');
        });


        // Add active class to current page
        menuItems.forEach(item => {
            if (item.href === currentUrl) {
                item.classList.add('active');
            }
        });
    });


    // ✅ Submenu toggle functionality
    document.querySelectorAll('[data-toggle="submenu"]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            const arrow = this.querySelector('.menu-arrow');


            if (submenu && submenu.classList.contains('submenu')) {
                if (submenu.style.display === 'block') {
                    submenu.style.display = 'none';
                    arrow.style.transform = 'rotate(0deg)';
                    this.classList.remove('active');
                } else {
                    submenu.style.display = 'block';
                    arrow.style.transform = 'rotate(180deg)';
                    this.classList.add('active');
                }
            }
        });
    });
</script>

{{-- only read --}}
