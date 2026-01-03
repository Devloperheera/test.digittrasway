<div class="main-content" id="mainContent">
    <nav class="top-nav">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="user-info">
            <div class="user-dropdown" onclick="toggleDropdown()">
                <!-- ✅ Display session data properly -->
                <span id="welcomeText">
                    @if (Session::has('admin_logged_in'))
                        Welcome, {{ Session::get('admin_name', 'Admin') }}!
                    @else
                        Welcome, Guest!
                    @endif
                </span>
                <div class="user-avatar">
                    <i class="fas fa-user-crown"></i>
                </div>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </div>

            <!-- ✅ User dropdown menu -->
            <div class="dropdown-menu" id="userDropdown">
                @if (Session::has('admin_logged_in'))
                    <!-- ✅ Profile route - Create karna padega -->
                    <a href="{{ route('admin.profile') }}" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>

                    <!-- ✅ Settings link -->
                    <a href="{{ route('admin.settings') }}" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>

                    <div class="dropdown-divider"></div>

                    <!-- ✅ Logout with confirmation -->
                    <a href="javascript:void(0)" class="dropdown-item" onclick="showLogoutConfirm()">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                @else
                    <!-- ✅ Login link for guests -->
                    <a href="{{ route('admin.login') }}" class="dropdown-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- ✅ Hidden Logout Form with correct route -->
        <form id="logoutForm" method="POST" action="{{ route('admin.logout') }}" style="display: none;">
            @csrf
        </form>
    </nav>



<!-- ✅ Enhanced JavaScript -->
<script>
    // ✅ Toggle dropdown function
    function toggleDropdown() {
        const dropdown = document.getElementById('userDropdown');
        const userDropdown = document.querySelector('.user-dropdown');

        dropdown.classList.toggle('show');
        userDropdown.classList.toggle('active');
    }

    // ✅ Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const userInfo = document.querySelector('.user-info');
        const dropdown = document.getElementById('userDropdown');

        if (!userInfo.contains(event.target)) {
            dropdown.classList.remove('show');
            document.querySelector('.user-dropdown').classList.remove('active');
        }
    });

    // ✅ Logout confirmation function
    function showLogoutConfirm() {
        // Close dropdown first
        document.getElementById('userDropdown').classList.remove('show');
        document.querySelector('.user-dropdown').classList.remove('active');

        Swal.fire({
            title: 'Confirm Logout',
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

                // Submit logout form
                setTimeout(() => {
                    document.getElementById('logoutForm').submit();
                }, 1000);
            }
        });
    }

    // ✅ Update welcome message dynamically
    document.addEventListener('DOMContentLoaded', function() {
        @if (Session::has('admin_logged_in'))
            const adminName = "{{ Session::get('admin_name', 'Admin') }}";
            document.getElementById('welcomeText').textContent = `Welcome, ${adminName}!`;
        @endif
    });
</script>
