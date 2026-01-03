<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - DigiTransway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative}.reset-wrapper{width:100%;max-width:450px;padding:20px}.reset-container{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);border-radius:24px;padding:48px 40px;box-shadow:0 25px 50px rgba(0,0,0,0.15);border:1px solid rgba(255,255,255,0.2);position:relative;overflow:hidden}.reset-container::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#2E4B7B 0%,#3A8F9D 50%,#4FC3D7 100%)}.reset-header{text-align:center;margin-bottom:36px}.reset-icon{width:80px;height:80px;margin:0 auto 24px;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);border-radius:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 32px rgba(46,75,123,0.3)}.reset-icon i{font-size:32px;color:white}.reset-title{font-size:28px;font-weight:700;color:#1a2332;margin-bottom:8px;letter-spacing:-0.5px}.reset-subtitle{font-size:15px;color:#64748b;font-weight:500;line-height:1.5}.email-display{background:#f0f9ff;border:1px solid #bfdbfe;border-radius:12px;padding:15px;margin-bottom:24px;text-align:center}.email-display strong{color:#2E4B7B}.form-section{margin-bottom:24px}.form-group{margin-bottom:24px;position:relative}.form-label{display:block;margin-bottom:8px;font-weight:600;color:#374151;font-size:14px}.input-container{position:relative}.form-input{width:100%;padding:16px 20px 16px 52px;border:2px solid #e5e7eb;border-radius:16px;font-size:16px;background:#fafafa;color:#374151;font-weight:500;transition:all 0.3s ease}.form-input:focus{outline:none;border-color:#3A8F9D;background:#fff;box-shadow:0 0 0 4px rgba(58,143,157,0.1)}.form-input::placeholder{color:#9ca3af}.input-icon{position:absolute;left:18px;top:50%;transform:translateY(-50%);color:#3A8F9D;font-size:18px}.toggle-password{position:absolute;right:18px;top:50%;transform:translateY(-50%);background:none;border:none;color:#64748b;cursor:pointer;font-size:18px;transition:color 0.3s ease}.toggle-password:hover{color:#3A8F9D}.password-strength{margin-top:12px;padding:8px 0}.strength-bar{height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;margin-bottom:8px}.strength-progress{height:100%;width:0%;border-radius:3px;transition:all 0.4s ease;background:#e5e7eb}.strength-weak{background:linear-gradient(90deg,#ef4444,#f87171);width:25%}.strength-fair{background:linear-gradient(90deg,#f59e0b,#fbbf24);width:50%}.strength-good{background:linear-gradient(90deg,#10b981,#34d399);width:75%}.strength-strong{background:linear-gradient(90deg,#059669,#10b981);width:100%}.strength-text{font-size:13px;font-weight:600;transition:color 0.3s ease}.strength-text.weak{color:#ef4444}.strength-text.fair{color:#f59e0b}.strength-text.good{color:#10b981}.strength-text.strong{color:#059669}.password-requirements{margin-top:12px}.requirement{display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:6px;transition:color 0.3s ease}.requirement i{font-size:12px;width:12px}.requirement.met{color:#10b981}.requirement.not-met{color:#64748b}.requirement.met i:before{content:'\f00c'}.requirement.not-met i:before{content:'\f00d'}.submit-button{width:100%;padding:18px;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);color:white;border:none;border-radius:16px;font-size:16px;font-weight:600;cursor:pointer;margin-bottom:24px;transition:all 0.3s ease;position:relative;overflow:hidden}.submit-button:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(46,75,123,0.4)}.submit-button:active{transform:translateY(0)}.submit-button:disabled{opacity:0.6;cursor:not-allowed;transform:none}.submit-button::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:left 0.6s}.submit-button:hover::before{left:100%}.back-section{text-align:center;padding-top:20px;border-top:1px solid #e5e7eb}.back-link{color:#3A8F9D;text-decoration:none;font-weight:600;font-size:14px;transition:color 0.3s ease;display:inline-flex;align-items:center;gap:8px}.back-link:hover{color:#2E4B7B}.back-link i{font-size:12px}.error-message{color:#ef4444;font-size:13px;margin-top:6px;font-weight:500}.success-message{color:#10b981;font-size:13px;margin-top:6px;font-weight:500}.loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center}.loading-spinner{width:48px;height:48px;border:4px solid rgba(255,255,255,0.3);border-top:4px solid #3A8F9D;border-radius:50%;animation:spin 1s linear infinite}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}@media (max-width:640px){.reset-wrapper{padding:16px}.reset-container{padding:36px 28px}.reset-icon{width:64px;height:64px}.reset-icon i{font-size:28px}.reset-title{font-size:24px}.password-requirements{margin-top:16px}.requirement{font-size:12px}}</style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="reset-wrapper">
        <div class="reset-container">
            <div class="reset-header">
                <div class="reset-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="reset-title">Reset Password</h1>
                <p class="reset-subtitle">Create a strong, secure password for your account</p>
            </div>

            <div class="email-display">
                <p>Resetting password for: <strong>{{ $email }}</strong></p>
            </div>

            <form method="POST" action="{{ route('admin.reset.password.post') }}" id="resetForm">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label" for="password">New Password</label>
                        <div class="input-container">
                            <input type="password" class="form-input" id="password" name="password"
                                   required placeholder="Enter your new password" minlength="8">
                            <i class="fas fa-key input-icon"></i>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>

                        <!-- Password Strength Indicator -->
                        <div class="password-strength" id="passwordStrength" style="display: none;">
                            <div class="strength-bar">
                                <div class="strength-progress" id="strengthProgress"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength</div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="password-requirements" id="passwordRequirements" style="display: none;">
                            <div class="requirement not-met" id="req-length">
                                <i class="fas"></i>
                                <span>At least 8 characters</span>
                            </div>
                            <div class="requirement not-met" id="req-lower">
                                <i class="fas"></i>
                                <span>One lowercase letter</span>
                            </div>
                            <div class="requirement not-met" id="req-upper">
                                <i class="fas"></i>
                                <span>One uppercase letter</span>
                            </div>
                            <div class="requirement not-met" id="req-number">
                                <i class="fas"></i>
                                <span>One number</span>
                            </div>
                            <div class="requirement not-met" id="req-special">
                                <i class="fas"></i>
                                <span>One special character</span>
                            </div>
                        </div>

                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Confirm Password</label>
                        <div class="input-container">
                            <input type="password" class="form-input" id="password_confirmation" name="password_confirmation"
                                   required placeholder="Confirm your new password" minlength="8">
                            <i class="fas fa-check-double input-icon"></i>
                            <button type="button" class="toggle-password" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" style="display: none; margin-top: 8px; font-size: 13px; font-weight: 500;"></div>

                        @error('password_confirmation')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="submit-button" id="submitBtn" disabled>
                        <i class="fas fa-shield-alt" style="margin-right: 8px;"></i>Reset Password
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

        // Password visibility toggle
        function togglePasswordVisibility(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        $('#togglePassword').on('click', () => togglePasswordVisibility('password', 'togglePassword'));
        $('#toggleConfirmPassword').on('click', () => togglePasswordVisibility('password_confirmation', 'toggleConfirmPassword'));

        // Password strength checker
        const requirements = {
            length: /^.{8,}$/,
            lower: /[a-z]/,
            upper: /[A-Z]/,
            number: /[0-9]/,
            special: /[^a-zA-Z0-9]/
        };

        function checkPasswordStrength(password) {
            let score = 0;
            let metRequirements = {};

            for (const [key, regex] of Object.entries(requirements)) {
                metRequirements[key] = regex.test(password);
                if (metRequirements[key]) score++;
            }

            return { score, metRequirements };
        }

        function updatePasswordUI(password) {
            const { score, metRequirements } = checkPasswordStrength(password);
            const strengthProgress = $('#strengthProgress');
            const strengthText = $('#strengthText');
            const submitBtn = $('#submitBtn');

            // Show/hide strength indicator
            if (password.length > 0) {
                $('#passwordStrength, #passwordRequirements').show();
            } else {
                $('#passwordStrength, #passwordRequirements').hide();
                submitBtn.prop('disabled', true);
                return;
            }

            // Update requirements display
            for (const [key, met] of Object.entries(metRequirements)) {
                const element = $(`#req-${key}`);
                element.removeClass('met not-met').addClass(met ? 'met' : 'not-met');
            }

            // Update strength bar and text
            strengthProgress.removeClass('strength-weak strength-fair strength-good strength-strong');
            strengthText.removeClass('weak fair good strong');

            if (score <= 2) {
                strengthProgress.addClass('strength-weak');
                strengthText.addClass('weak').text('Weak password');
            } else if (score === 3) {
                strengthProgress.addClass('strength-fair');
                strengthText.addClass('fair').text('Fair password');
            } else if (score === 4) {
                strengthProgress.addClass('strength-good');
                strengthText.addClass('good').text('Good password');
            } else {
                strengthProgress.addClass('strength-strong');
                strengthText.addClass('strong').text('Strong password');
            }
        }

        function checkPasswordMatch() {
            const password = $('#password').val();
            const confirmPassword = $('#password_confirmation').val();
            const matchDiv = $('#passwordMatch');
            const submitBtn = $('#submitBtn');

            if (confirmPassword.length === 0) {
                matchDiv.hide();
                return false;
            }

            matchDiv.show();

            if (password === confirmPassword) {
                matchDiv.html('<i class="fas fa-check" style="color: #10b981; margin-right: 4px;"></i>Passwords match').css('color', '#10b981');
                return true;
            } else {
                matchDiv.html('<i class="fas fa-times" style="color: #ef4444; margin-right: 4px;"></i>Passwords do not match').css('color', '#ef4444');
                return false;
            }
        }

        function updateSubmitButton() {
            const password = $('#password').val();
            const { score } = checkPasswordStrength(password);
            const passwordsMatch = checkPasswordMatch();
            const submitBtn = $('#submitBtn');

            // Enable submit button only if password strength is good/strong and passwords match
            if (score >= 4 && passwordsMatch) {
                submitBtn.prop('disabled', false);
            } else {
                submitBtn.prop('disabled', true);
            }
        }

        // Event listeners
        $('#password').on('input', function() {
            const password = this.value;
            updatePasswordUI(password);
            updateSubmitButton();
        });

        $('#password_confirmation').on('input', function() {
            updateSubmitButton();
        });

        // Form submission
        $('#resetForm').on('submit', function(e) {
            e.preventDefault();

            const password = $('#password').val();
            const { score } = checkPasswordStrength(password);

            if (score < 4) {
                toastr.error('Please create a stronger password');
                return;
            }

            if (!checkPasswordMatch()) {
                toastr.error('Passwords do not match');
                return;
            }

            // Show loading state
            const submitBtn = $('#submitBtn');
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Resetting Password...');
            $('#loadingOverlay').css('display', 'flex');

            // Submit form after delay
            setTimeout(() => {
                this.submit();
            }, 1000);
        });

        // Auto-focus on password field
        $(document).ready(function() {
            $('#password').focus();
        });
    </script>
</body>
</html>
