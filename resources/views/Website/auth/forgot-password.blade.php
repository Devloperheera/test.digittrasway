<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Digit Transway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative}.forgot-wrapper{width:100%;max-width:450px;padding:20px}.forgot-container{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);border-radius:24px;padding:48px 40px;box-shadow:0 25px 50px rgba(0,0,0,0.15);border:1px solid rgba(255,255,255,0.2);position:relative;overflow:hidden}.forgot-container::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#2E4B7B 0%,#3A8F9D 50%,#4FC3D7 100%)}.forgot-header{text-align:center;margin-bottom:36px}.company-logo{width:100px;height:100px;margin:0 auto 24px;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);border-radius:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 32px rgba(46,75,123,0.3)}.company-logo i{font-size:40px;color:white}.forgot-title{font-size:28px;font-weight:700;color:#1a2332;margin-bottom:8px;letter-spacing:-0.5px}.forgot-subtitle{font-size:15px;color:#64748b;font-weight:500;line-height:1.5}.info-section{background:linear-gradient(135deg,#EFF8FF 0%,#F0F9FF 100%);border:1px solid #BFDBFE;border-radius:16px;padding:20px;margin-bottom:28px;text-align:center}.info-section .info-icon{width:48px;height:48px;background:linear-gradient(135deg,#3A8F9D 0%,#4FC3D7 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px}.info-section .info-icon i{font-size:20px;color:white}.info-text{font-size:14px;color:#374151;font-weight:500}.form-section{margin-bottom:24px}.form-group{margin-bottom:24px;position:relative}.form-label{display:block;margin-bottom:8px;font-weight:600;color:#374151;font-size:14px}.input-container{position:relative}.form-input{width:100%;padding:16px 20px 16px 52px;border:2px solid #e5e7eb;border-radius:16px;font-size:16px;background:#fafafa;color:#374151;font-weight:500;transition:all 0.3s ease}.form-input:focus{outline:none;border-color:#3A8F9D;background:#fff;box-shadow:0 0 0 4px rgba(58,143,157,0.1)}.form-input::placeholder{color:#9ca3af}.input-icon{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:#3A8F9D;font-size:18px}.submit-button{width:100%;padding:18px;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);color:white;border:none;border-radius:16px;font-size:16px;font-weight:600;cursor:pointer;margin-bottom:24px;transition:transform 0.2s ease,box-shadow 0.3s ease}.submit-button:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(46,75,123,0.4)}.submit-button:active{transform:translateY(0)}.submit-button:disabled{opacity:0.6;cursor:not-allowed;transform:none}.back-section{text-align:center;padding-top:20px;border-top:1px solid #e5e7eb}.back-link{color:#3A8F9D;text-decoration:none;font-weight:600;font-size:14px;transition:color 0.3s ease;display:inline-flex;align-items:center;gap:8px}.back-link:hover{color:#2E4B7B}.back-link i{font-size:12px}.error-message{color:#ef4444;font-size:13px;margin-top:6px;font-weight:500}.success-message{color:#10b981;font-size:13px;margin-top:6px;font-weight:500}.loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center}.loading-spinner{width:48px;height:48px;border:4px solid rgba(255,255,255,0.3);border-top:4px solid #3A8F9D;border-radius:50%;animation:spin 1s linear infinite}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@media (max-width:640px){.forgot-wrapper{padding:16px}.forgot-container{padding:36px 28px}.company-logo{width:80px;height:80px}.company-logo i{font-size:32px}.forgot-title{font-size:24px}}</style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="forgot-wrapper">
        <div class="forgot-container">
            <div class="forgot-header">
                <div class="company-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="forgot-title">Forgot Password?</h1>
                <p class="forgot-subtitle">Enter your registered email address to receive a secure OTP verification code.</p>
            </div>

            <div class="info-section">
                {{-- <div class="info-icon">
                    <i class="fas fa-shield-alt"></i>
                </div> --}}
                <p class="info-text">
                    <strong>Secure Process:</strong> We'll send a 6-digit verification code to your registered admin email. The code expires in 10 minutes for security.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.forgot.password.post') }}" id="forgotForm">
                @csrf

                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label" for="email">Admin Email Address</label>
                        <div class="input-container">
                            <input type="email" class="form-input" id="email" name="email"
                                   value="{{ old('email') }}" required placeholder="Enter your admin email address">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror

                        <div style="margin-top: 8px; font-size: 12px; color: #64748b;">
                            <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                            Only registered admin emails can receive password reset codes
                        </div>
                    </div>

                    <button type="submit" class="submit-button" id="submitBtn">
                        <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>Send Verification Code
                    </button>
                </div>
            </form>

            <div class="back-section">
                <a href="{{ route('admin.login') }}" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Login</span>
                </a>
            </div>
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

        $('#forgotForm').on('submit', function(e) {
            e.preventDefault();

            const submitBtn = $('#submitBtn');
            const email = $('#email').val();

            // Validate email
            if (!email || !email.includes('@')) {
                toastr.error('Please enter a valid email address');
                return;
            }

            // Show loading state
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Sending Code...');
            $('#loadingOverlay').css('display', 'flex');

            // Submit form after delay
            setTimeout(() => {
                this.submit();
            }, 1200);
        });
    </script>
</body>
</html>
