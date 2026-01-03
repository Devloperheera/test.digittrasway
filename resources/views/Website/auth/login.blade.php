<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Digit Transway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative}.login-wrapper{width:100%;max-width:450px;padding:20px}.login-container{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);border-radius:24px;padding:48px 40px;box-shadow:0 25px 50px rgba(0,0,0,0.15);border:1px solid rgba(255,255,255,0.2);position:relative;overflow:hidden}.login-container::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#2E4B7B 0%,#3A8F9D 50%,#4FC3D7 100%)}.login-header{text-align:center;margin-bottom:36px}.company-logo{width:120px;height:120px;margin:0 auto 24px;background:white;border-radius:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 32px rgba(46,75,123,0.2);position:relative}.company-logo img{width:90px;height:auto;object-fit:contain}.brand-title{font-size:28px;font-weight:700;color:#1a2332;margin-bottom:8px;letter-spacing:-0.5px}.brand-subtitle{font-size:15px;color:#64748b;font-weight:500}.form-section{margin-bottom:24px}.form-group{margin-bottom:24px;position:relative}.form-label{display:block;margin-bottom:8px;font-weight:600;color:#374151;font-size:14px}.input-container{position:relative}.form-input{width:100%;padding:16px 20px 16px 52px;border:2px solid #e5e7eb;border-radius:16px;font-size:16px;background:#fafafa;color:#374151;font-weight:500;transition:all 0.3s ease}.form-input:focus{outline:none;border-color:#3A8F9D;background:#fff;box-shadow:0 0 0 4px rgba(58,143,157,0.1)}.form-input::placeholder{color:#9ca3af}.input-icon{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:#3A8F9D;font-size:18px}.remember-section{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px}.remember-checkbox{display:flex;align-items:center;gap:8px}.remember-checkbox input[type="checkbox"]{width:18px;height:18px;accent-color:#3A8F9D;cursor:pointer}.remember-checkbox label{color:#64748b;font-size:14px;cursor:pointer;font-weight:500}.forgot-link{color:#3A8F9D;text-decoration:none;font-weight:600;font-size:14px;transition:color 0.3s ease}.forgot-link:hover{color:#2E4B7B}.login-button{width:100%;padding:18px;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);color:white;border:none;border-radius:16px;font-size:16px;font-weight:600;cursor:pointer;transition:transform 0.2s ease,box-shadow 0.3s ease}.login-button:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(46,75,123,0.4)}.login-button:active{transform:translateY(0)}.error-message{color:#ef4444;font-size:13px;margin-top:6px;font-weight:500}.loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center}.loading-spinner{width:48px;height:48px;border:4px solid rgba(255,255,255,0.3);border-top:4px solid #3A8F9D;border-radius:50%;animation:spin 1s linear infinite}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@media (max-width:640px){.login-wrapper{padding:16px}.login-container{padding:36px 28px}.company-logo{width:100px;height:100px}.company-logo img{width:75px}.brand-title{font-size:24px}}</style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="company-logo">
                    <img src="{{ asset('web_assets/images/logo.png') }}" alt="Digit Transway Logo">
                </div>
                <h1 class="brand-title">Digit Transway</h1>
                <p class="brand-subtitle">Admin Portal Access</p>
            </div>

            <form method="POST" action="{{ route('admin.login.post') }}" id="loginForm">
                @csrf

                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-container">
                            <input type="email" class="form-input" id="email" name="email"
                                   value="{{ old('email') }}" required placeholder="Enter your email address">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-container">
                            <input type="password" class="form-input" id="password" name="password"
                                   required placeholder="Enter your password">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="remember-section">
                        <div class="remember-checkbox">
                            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember">Keep me signed in</label>
                        </div>
                        <a href="{{ route('admin.forgot.password') }}" class="forgot-link">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-button">
                        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        toastr.options = {
            "closeButton": true,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        @if(session('toastr'))
            @php $toastr = session('toastr'); @endphp
            toastr.{{ $toastr['type'] }}('{{ $toastr['message'] }}');
        @endif

        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            $('#loadingOverlay').css('display', 'flex');
            setTimeout(() => { this.submit(); }, 800);
        });
    </script>
</body>
</html>
