@extends('Website.Layout.master')

@section('custom_css')
<style>
    :root {
        --primary-color: #2E86AB;
        --secondary-color: #48A9A6;
        --accent-color: #F18F01;
        --success-color: #28a745;
        --dark-color: #1B4B6B;
        --light-color: #F8F9FA;
        --white: #FFFFFF;
        --shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .profile-container {
        background: linear-gradient(135deg, var(--light-color) 0%, #fff 100%);
        min-height: 100vh;
        padding: 30px 0;
    }

    .profile-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 40px;
        border-radius: 20px 20px 0 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
    }

    .profile-header .content {
        position: relative;
        z-index: 1;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        backdrop-filter: blur(10px);
        border: 3px solid rgba(255,255,255,0.3);
    }

    .profile-avatar i {
        font-size: 50px;
        color: white;
    }

    .profile-name {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px;
    }

    .profile-role {
        font-size: 16px;
        opacity: 0.9;
        font-weight: 500;
    }

    .profile-content {
        background: white;
        border-radius: 0 0 20px 20px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .tab-navigation {
        display: flex;
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
    }

    .tab-btn {
        flex: 1;
        padding: 20px;
        background: transparent;
        border: none;
        font-size: 16px;
        font-weight: 600;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .tab-btn.active {
        color: var(--primary-color);
        background: white;
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--primary-color);
    }

    .tab-btn:hover {
        color: var(--primary-color);
        background: rgba(46, 134, 171, 0.05);
    }

    .tab-content {
        padding: 40px;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .info-card {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        border-left: 5px solid var(--primary-color);
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-value {
        color: #6c757d;
        font-weight: 500;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 20px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: rgba(255,255,255,0.8);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(46, 134, 171, 0.1);
        background: white;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border: none;
        padding: 15px 30px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(46, 134, 171, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        border: none;
        padding: 15px 30px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .password-strength {
        margin-top: 10px;
        padding: 10px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
    }

    .strength-weak {
        background: #f8d7da;
        color: #721c24;
    }

    .strength-medium {
        background: #fff3cd;
        color: #856404;
    }

    .strength-strong {
        background: #d4edda;
        color: #155724;
    }

    .alert {
        border-radius: 12px;
        padding: 15px 20px;
        border: none;
        margin-bottom: 20px;
    }

    .alert-success {
        background: var(--success-color);
        color: white;
    }

    .alert-danger {
        background: #dc3545;
        color: white;
    }

    @media (max-width: 768px) {
        .tab-navigation {
            flex-direction: column;
        }

        .tab-content {
            padding: 20px;
        }

        .profile-header {
            padding: 30px 20px;
        }
    }
</style>
@endsection

@section('content')
<div class="profile-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="content">
                        <div class="profile-avatar">
                            <i class="fas fa-user-crown"></i>
                        </div>
                        <h2 class="profile-name">{{ $adminData['name'] }}</h2>
                        <p class="profile-role">{{ $adminData['role'] }}</p>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="profile-content">
                    <!-- Tab Navigation -->
                    <div class="tab-navigation">
                        <button class="tab-btn active" data-tab="profile-info">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </button>
                        <button class="tab-btn" data-tab="change-password">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </button>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Profile Information Tab -->
                        <div class="tab-pane active" id="profile-info">
                            <!-- Display Messages -->
                            @if(session('profile_success'))
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>{{ session('profile_success') }}
                                </div>
                            @endif

                            @if(session('profile_error'))
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('profile_error') }}
                                </div>
                            @endif

                            <!-- Current Profile Info -->
                            <div class="info-card">
                                <h4 style="color: var(--primary-color); margin-bottom: 20px;">
                                    <i class="fas fa-info-circle me-2"></i>Current Information
                                </h4>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-user"></i>Full Name
                                    </div>
                                    <div class="info-value">{{ $adminData['name'] }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-envelope"></i>Email Address
                                    </div>
                                    <div class="info-value">{{ $adminData['email'] }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-shield-alt"></i>Role
                                    </div>
                                    <div class="info-value">{{ $adminData['role'] }}</div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-alt"></i>Last Updated
                                    </div>
                                    <div class="info-value">{{ date('F d, Y') }}</div>
                                </div>
                            </div>

                            <!-- Update Profile Form -->
                            <div class="card">
                                <div class="card-body">
                                    <h4 style="color: var(--primary-color); margin-bottom: 20px;">
                                        <i class="fas fa-edit me-2"></i>Update Profile Information
                                    </h4>

                                    <form action="{{ route('admin.profile.update') }}" method="POST">
                                        @csrf

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-user"></i>Full Name
                                            </label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                   name="name" value="{{ old('name', $adminData['name']) }}"
                                                   placeholder="Enter your full name" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-envelope"></i>Email Address
                                            </label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                   name="email" value="{{ old('email', $adminData['email']) }}"
                                                   placeholder="Enter your email address" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="d-flex gap-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Profile
                                            </button>
                                            <button type="reset" class="btn btn-secondary">
                                                <i class="fas fa-undo me-2"></i>Reset
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Change Password Tab -->
                        <div class="tab-pane" id="change-password">
                            <!-- Display Messages -->
                            @if(session('password_success'))
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>{{ session('password_success') }}
                                </div>
                            @endif

                            @if(session('password_error'))
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('password_error') }}
                                </div>
                            @endif

                            <div class="info-card">
                                <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                                    <i class="fas fa-shield-alt me-2"></i>Password Security
                                </h4>
                                <p class="mb-0 text-muted">
                                    Keep your account secure by using a strong password. Your password should be at least 8 characters long and contain a mix of letters, numbers, and special characters.
                                </p>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <h4 style="color: var(--primary-color); margin-bottom: 20px;">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </h4>

                                    <form action="{{ route('admin.password.update') }}" method="POST" id="passwordForm">
                                        @csrf

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-lock"></i>Current Password
                                            </label>
                                            <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                                   name="current_password" placeholder="Enter your current password" required>
                                            @error('current_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-key"></i>New Password
                                            </label>
                                            <input type="password" class="form-control @error('new_password') is-invalid @enderror"
                                                   name="new_password" id="newPassword" placeholder="Enter your new password" required>
                                            @error('new_password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="password-strength" id="passwordStrength" style="display: none;"></div>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-check-double"></i>Confirm New Password
                                            </label>
                                            <input type="password" class="form-control @error('new_password_confirmation') is-invalid @enderror"
                                                   name="new_password_confirmation" id="confirmPassword"
                                                   placeholder="Confirm your new password" required>
                                            @error('new_password_confirmation')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div id="passwordMatch" class="mt-2" style="display: none;"></div>
                                        </div>

                                        <div class="d-flex gap-3">
                                            <button type="submit" class="btn btn-primary" id="updatePasswordBtn">
                                                <i class="fas fa-shield-alt me-2"></i>Update Password
                                            </button>
                                            <button type="reset" class="btn btn-secondary">
                                                <i class="fas fa-undo me-2"></i>Reset
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('custom_js')
<script>
    // Tab switching functionality
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs and buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

            // Add active class to clicked button
            this.classList.add('active');

            // Show corresponding tab content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // Password strength checker
    document.getElementById('newPassword').addEventListener('input', function() {
        const password = this.value;
        const strengthDiv = document.getElementById('passwordStrength');

        if (password.length === 0) {
            strengthDiv.style.display = 'none';
            return;
        }

        strengthDiv.style.display = 'block';

        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        strengthDiv.classList.remove('strength-weak', 'strength-medium', 'strength-strong');

        if (strength < 3) {
            strengthDiv.classList.add('strength-weak');
            strengthDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Weak password';
        } else if (strength < 5) {
            strengthDiv.classList.add('strength-medium');
            strengthDiv.innerHTML = '<i class="fas fa-shield-alt me-2"></i>Medium strength password';
        } else {
            strengthDiv.classList.add('strength-strong');
            strengthDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Strong password';
        }
    });

    // Password match checker
    function checkPasswordMatch() {
        const password = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const matchDiv = document.getElementById('passwordMatch');

        if (confirmPassword.length === 0) {
            matchDiv.style.display = 'none';
            return;
        }

        matchDiv.style.display = 'block';

        if (password === confirmPassword) {
            matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Passwords match</small>';
        } else {
            matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Passwords do not match</small>';
        }
    }

    document.getElementById('newPassword').addEventListener('input', checkPasswordMatch);
    document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);

    // Form submission with loading state
    document.getElementById('passwordForm').addEventListener('submit', function() {
        const btn = document.getElementById('updatePasswordBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        btn.disabled = true;
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
@endsection
