    // Set user name and CSRF token when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Set CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            document.getElementById('csrfToken').value = csrfToken.getAttribute('content');
        }

        // You can set user name from PHP/Session here if needed
        // document.getElementById('welcomeText').textContent = 'Welcome, <?php echo session('user_name', 'Guest'); ?>!';
    });

    function toggleDropdown() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('show');
    }

    function closeDropdown() {
        document.getElementById('userDropdown').classList.remove('show');
    }

    function openProfile() {
        closeDropdown();
        window.location.href = '/profile';
    }

    function showLogoutConfirm() {
        closeDropdown();

        Swal.fire({
            title: 'Logout',
            text: 'Are you sure you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2b3559',
            cancelButtonColor: '#dea410'
        }).then((result) => {
            if (result.isConfirmed) {
                performLogout();
            }
        });
    }

    function performLogout() {
        // Show loading
        Swal.fire({
            title: 'Logging out...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit logout form
        document.getElementById('logoutForm').submit();
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        const userInfo = document.querySelector('.user-info');

        if (userInfo && !userInfo.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
