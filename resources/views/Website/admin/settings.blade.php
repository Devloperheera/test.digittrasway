@extends('Website.Layout.master')

@section('custom_css')
<style>
    :root {
        --primary-color: #2E86AB;
        --secondary-color: #48A9A6;
        --accent-color: #F18F01;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --dark-color: #1B4B6B;
        --light-color: #F8F9FA;
        --white: #FFFFFF;
        --shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .settings-container {
        background: linear-gradient(135deg, var(--light-color) 0%, #fff 100%);
        min-height: 100vh;
        padding: 30px 0;
    }

    .settings-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 40px;
        border-radius: 20px;
        text-align: center;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .settings-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
    }

    .settings-header .content {
        position: relative;
        z-index: 1;
    }

    .settings-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255,255,255,0.3);
    }

    .settings-icon i {
        font-size: 35px;
        color: white;
    }

    .settings-nav {
        background: white;
        border-radius: 15px;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .nav-pills .nav-link {
        border-radius: 0;
        padding: 20px 25px;
        color: #6c757d;
        font-weight: 600;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .nav-pills .nav-link:last-child {
        border-bottom: none;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        position: relative;
    }

    .nav-pills .nav-link.active::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--accent-color);
    }

    .nav-pills .nav-link:hover:not(.active) {
        background: rgba(46, 134, 171, 0.05);
        color: var(--primary-color);
        transform: translateX(5px);
    }

    .settings-content {
        background: white;
        border-radius: 15px;
        box-shadow: var(--shadow);
        padding: 40px;
    }

    .section-title {
        color: var(--dark-color);
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-subtitle {
        color: #6c757d;
        font-size: 16px;
        margin-bottom: 30px;
        line-height: 1.6;
    }

    .setting-item {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        border-left: 4px solid var(--primary-color);
        transition: all 0.3s ease;
    }

    .setting-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .setting-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .setting-item-title {
        font-weight: 700;
        color: var(--dark-color);
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .setting-item-description {
        color: #6c757d;
        font-size: 14px;
        margin-bottom: 15px;
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

    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: rgba(255,255,255,0.8);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(46, 134, 171, 0.1);
        background: white;
    }

    .form-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
        background-color: #e9ecef;
        border: none;
        background-image: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .form-switch .form-check-input:checked {
        background-color: var(--success-color);
        border-color: var(--success-color);
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border: none;
        padding: 15px 30px;
        border-radius: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(46, 134, 171, 0.3);
    }

    .btn-outline-primary {
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        padding: 15px 30px;
        border-radius: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        background: var(--primary-color);
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .btn-danger {
        background: var(--danger-color);
        border: none;
        padding: 15px 30px;
        border-radius: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
    }

    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        border: none;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success-color);
        border-left: 4px solid var(--success-color);
    }

    .alert-warning {
        background: rgba(255, 193, 7, 0.1);
        color: var(--warning-color);
        border-left: 4px solid var(--warning-color);
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 15px;
        text-align: center;
        margin-bottom: 20px;
    }

    .stats-number {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .stats-label {
        font-size: 14px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (max-width: 991px) {
        .nav-pills .nav-link {
            padding: 15px 20px;
        }

        .settings-content {
            padding: 25px;
        }

        .setting-item {
            padding: 20px;
        }
    }
</style>
@endsection

@section('content')
<div class="settings-container">
    <div class="container-fluid">
        <!-- Settings Header -->
        <div class="settings-header">
            <div class="content">
                <div class="settings-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h1 style="margin: 0; font-size: 32px; font-weight: 700;">{{ $pageTitle }}</h1>
                <p style="margin: 8px 0 0; font-size: 16px; opacity: 0.9;">
                    Manage your application settings and preferences
                </p>
            </div>
        </div>

        <div class="row">
            <!-- Navigation Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="settings-nav">
                    <ul class="nav nav-pills flex-column" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="pill" href="#general-settings">
                                <i class="fas fa-sliders-h"></i>
                                <span>General Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#appearance">
                                <i class="fas fa-palette"></i>
                                <span>Appearance</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#notifications">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#security">
                                <i class="fas fa-shield-alt"></i>
                                <span>Security</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#backup">
                                <i class="fas fa-database"></i>
                                <span>Backup & Restore</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" href="#system">
                                <i class="fas fa-server"></i>
                                <span>System Info</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-lg-9 col-md-8">
                <div class="tab-content">
                    <!-- General Settings Tab -->
                    <div class="tab-pane fade show active" id="general-settings">
                        <div class="settings-content">
                            <!-- Success Messages -->
                            @if(session('settings_success'))
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>{{ session('settings_success') }}
                                </div>
                            @endif

                            <div class="section-title">
                                <i class="fas fa-sliders-h"></i>General Settings
                            </div>
                            <div class="section-subtitle">
                                Configure basic application settings and preferences.
                            </div>

                            <form action="{{ route('admin.settings.update') }}" method="POST">
                                @csrf

                                <div class="setting-item">
                                    <div class="setting-item-header">
                                        <div class="setting-item-title">
                                            <i class="fas fa-globe"></i>Site Configuration
                                        </div>
                                    </div>
                                    <div class="setting-item-description">
                                        Basic site information and configuration
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-tag"></i>Site Name
                                                </label>
                                                <input type="text" class="form-control" name="site_name"
                                                       value="MKSK Admin Panel" placeholder="Enter site name">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-envelope"></i>Admin Email
                                                </label>
                                                <input type="email" class="form-control" name="admin_email"
                                                       value="admin@admin.com" placeholder="Enter admin email">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-align-left"></i>Site Description
                                        </label>
                                        <textarea class="form-control" name="site_description" rows="3"
                                                  placeholder="Enter site description">Premium Admin Panel for Business Management</textarea>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-item-header">
                                        <div class="setting-item-title">
                                            <i class="fas fa-clock"></i>Time & Date Settings
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-globe-asia"></i>Timezone
                                                </label>
                                                <select class="form-select" name="timezone">
                                                    <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                                                    <option value="America/New_York">America/New_York (EST)</option>
                                                    <option value="Europe/London">Europe/London (GMT)</option>
                                                    <option value="Australia/Sydney">Australia/Sydney (AEST)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-calendar-alt"></i>Date Format
                                                </label>
                                                <select class="form-select" name="date_format">
                                                    <option value="Y-m-d" selected>YYYY-MM-DD</option>
                                                    <option value="d/m/Y">DD/MM/YYYY</option>
                                                    <option value="m/d/Y">MM/DD/YYYY</option>
                                                    <option value="d-M-Y">DD-Mon-YYYY</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <button type="reset" class="btn btn-outline-primary">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Appearance Tab -->
                    <div class="tab-pane fade" id="appearance">
                        <div class="settings-content">
                            <div class="section-title">
                                <i class="fas fa-palette"></i>Appearance Settings
                            </div>
                            <div class="section-subtitle">
                                Customize the look and feel of your admin panel.
                            </div>

                            <form action="{{ route('admin.appearance.update') }}" method="POST">
                                @csrf

                                <div class="setting-item">
                                    <div class="setting-item-header">
                                        <div class="setting-item-title">
                                            <i class="fas fa-paint-brush"></i>Theme Settings
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-palette"></i>Color Scheme
                                                </label>
                                                <select class="form-select" name="color_scheme">
                                                    <option value="blue" selected>Ocean Blue</option>
                                                    <option value="green">Forest Green</option>
                                                    <option value="purple">Royal Purple</option>
                                                    <option value="orange">Sunset Orange</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-adjust"></i>Theme Mode
                                                </label>
                                                <select class="form-select" name="theme_mode">
                                                    <option value="light" selected>Light Mode</option>
                                                    <option value="dark">Dark Mode</option>
                                                    <option value="auto">Auto (System)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="sidebar_collapse" checked>
                                        <label class="form-check-label">
                                            Enable Sidebar Collapse
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Appearance
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Notifications Tab -->
                    <div class="tab-pane fade" id="notifications">
                        <div class="settings-content">
                            <div class="section-title">
                                <i class="fas fa-bell"></i>Notification Settings
                            </div>
                            <div class="section-subtitle">
                                Control how and when you receive notifications.
                            </div>

                            <form action="{{ route('admin.notifications.update') }}" method="POST">
                                @csrf

                                <div class="setting-item">
                                    <div class="setting-item-header">
                                        <div class="setting-item-title">
                                            <i class="fas fa-envelope"></i>Email Notifications
                                        </div>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications" checked>
                                        <label class="form-check-label">
                                            Enable Email Notifications
                                        </label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="login_alerts" checked>
                                        <label class="form-check-label">
                                            Login Security Alerts
                                        </label>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="system_updates">
                                        <label class="form-check-label">
                                            System Update Notifications
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Preferences
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security">
                        <div class="settings-content">
                            <div class="section-title">
                                <i class="fas fa-shield-alt"></i>Security Settings
                            </div>
                            <div class="section-subtitle">
                                Manage security settings and access controls.
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Changes to security settings will affect all admin users.
                            </div>

                            <form action="{{ route('admin.security.update') }}" method="POST">
                                @csrf

                                <div class="setting-item">
                                    <div class="setting-item-header">
                                        <div class="setting-item-title">
                                            <i class="fas fa-lock"></i>Session Settings
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-clock"></i>Session Timeout (minutes)
                                                </label>
                                                <input type="number" class="form-control" name="session_timeout"
                                                       value="120" min="5" max="1440">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-shield-alt"></i>Max Login Attempts
                                                </label>
                                                <input type="number" class="form-control" name="max_login_attempts"
                                                       value="5" min="3" max="10">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="force_https" checked>
                                        <label class="form-check-label">
                                            Force HTTPS Connection
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-shield-alt me-2"></i>Update Security
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Backup Tab -->
                    <div class="tab-pane fade" id="backup">
                        <div class="settings-content">
                            <div class="section-title">
                                <i class="fas fa-database"></i>Backup & Restore
                            </div>
                            <div class="section-subtitle">
                                Manage system backups and data restoration.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="stats-card">
                                        <div class="stats-number">5</div>
                                        <div class="stats-label">Available Backups</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                        <div class="stats-number">2.3GB</div>
                                        <div class="stats-label">Total Backup Size</div>
                                    </div>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-item-header">
                                    <div class="setting-item-title">
                                        <i class="fas fa-download"></i>Create Backup
                                    </div>
                                </div>
                                <div class="setting-item-description">
                                    Create a complete backup of your system and database.
                                </div>

                                <div class="d-flex gap-3">
                                    <button type="button" class="btn btn-primary" onclick="createBackup()">
                                        <i class="fas fa-plus me-2"></i>Create New Backup
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="viewBackups()">
                                        <i class="fas fa-list me-2"></i>View All Backups
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Info Tab -->
                    <div class="tab-pane fade" id="system">
                        <div class="settings-content">
                            <div class="section-title">
                                <i class="fas fa-server"></i>System Information
                            </div>
                            <div class="section-subtitle">
                                View system status and technical information.
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                        <div class="stats-number">{{ PHP_VERSION }}</div>
                                        <div class="stats-label">PHP Version</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                        <div class="stats-number">{{ app()->version() }}</div>
                                        <div class="stats-label">Laravel Version</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stats-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                        <div class="stats-number">{{ date('Y-m-d') }}</div>
                                        <div class="stats-label">Last Update</div>
                                    </div>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-item-header">
                                    <div class="setting-item-title">
                                        <i class="fas fa-info-circle"></i>System Status
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Environment:</strong> {{ app()->environment() }}</p>
                                        <p><strong>Debug Mode:</strong> {{ config('app.debug') ? 'Enabled' : 'Disabled' }}</p>
                                        <p><strong>Database:</strong> {{ config('database.default') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Cache Driver:</strong> {{ config('cache.default') }}</p>
                                        <p><strong>Session Driver:</strong> {{ config('session.driver') }}</p>
                                        <p><strong>Queue Driver:</strong> {{ config('queue.default') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-primary" onclick="clearCache()">
                                    <i class="fas fa-broom me-2"></i>Clear All Cache
                                </button>
                                <button type="button" class="btn btn-outline-primary" onclick="optimizeSystem()">
                                    <i class="fas fa-rocket me-2"></i>Optimize System
                                </button>
                                <button type="button" class="btn btn-danger" onclick="maintenanceMode()">
                                    <i class="fas fa-tools me-2"></i>Maintenance Mode
                                </button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.20/sweetalert2.all.min.js"></script>
<script>
    // Create Backup Function
    function createBackup() {
        Swal.fire({
            title: 'Create System Backup',
            text: 'This will create a complete backup of your system. This may take a few minutes.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Create Backup',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2E86AB'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Creating Backup...',
                    text: 'Please wait while we create your backup',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Simulate backup process
                setTimeout(() => {
                    Swal.fire({
                        title: 'Backup Created!',
                        text: 'Your system backup has been created successfully.',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    });
                }, 3000);
            }
        });
    }

    // View Backups Function
    function viewBackups() {
        Swal.fire({
            title: 'Available Backups',
            html: `
                <div style="text-align: left;">
                    <p><strong>backup_2025_09_26.zip</strong> - 2.3 GB - Today</p>
                    <p><strong>backup_2025_09_25.zip</strong> - 2.1 GB - Yesterday</p>
                    <p><strong>backup_2025_09_20.zip</strong> - 2.0 GB - 6 days ago</p>
                    <p><strong>backup_2025_09_15.zip</strong> - 1.9 GB - 11 days ago</p>
                    <p><strong>backup_2025_09_10.zip</strong> - 1.8 GB - 16 days ago</p>
                </div>
            `,
            width: 500,
            showCloseButton: true,
            showConfirmButton: false
        });
    }

    // Clear Cache Function
    function clearCache() {
        Swal.fire({
            title: 'Clear All Cache',
            text: 'This will clear all cached data including routes, config, and views.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Clear Cache',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Clearing Cache...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                setTimeout(() => {
                    Swal.fire({
                        title: 'Cache Cleared!',
                        text: 'All cache has been cleared successfully.',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    });
                }, 2000);
            }
        });
    }

    // Optimize System Function
    function optimizeSystem() {
        Swal.fire({
            title: 'Optimize System',
            text: 'This will optimize your Laravel application for better performance.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Optimize',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2E86AB'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Optimizing...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                setTimeout(() => {
                    Swal.fire({
                        title: 'System Optimized!',
                        text: 'Your system has been optimized for better performance.',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    });
                }, 3000);
            }
        });
    }

    // Maintenance Mode Function
    function maintenanceMode() {
        Swal.fire({
            title: 'Enable Maintenance Mode',
            text: 'This will put your application in maintenance mode. Users won\'t be able to access the site.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Enable Maintenance',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Maintenance Mode Enabled',
                    text: 'Your application is now in maintenance mode.',
                    icon: 'info',
                    confirmButtonColor: '#6c757d'
                });
            }
        });
    }

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
