<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - DigiTransway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:0 10px}.otp-wrapper{width:100%;max-width:420px;padding:16px}.otp-container{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);border-radius:20px;padding:40px 30px;box-shadow:0 20px 40px rgba(0,0,0,0.15);border:1px solid rgba(255,255,255,0.2);position:relative;overflow:hidden;margin:0 auto}.otp-container::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#2E4B7B 0%,#3A8F9D 50%,#4FC3D7 100%)}.otp-header{text-align:center;margin-bottom:32px}.otp-icon{width:70px;height:70px;margin:0 auto 20px;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);border-radius:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 24px rgba(46,75,123,0.3)}.otp-icon i{font-size:28px;color:white}.otp-title{font-size:24px;font-weight:700;color:#1a2332;margin-bottom:6px;letter-spacing:-0.3px}.otp-subtitle{font-size:14px;color:#64748b;font-weight:500;line-height:1.4;max-width:300px;margin:0 auto}.email-display{background:#f0f9ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px 16px;margin-bottom:24px;text-align:center;font-size:13px;word-break:break-all}.email-display strong{color:#2E4B7B;font-weight:600}.otp-inputs{display:flex;justify-content:center;align-items:center;gap:8px;margin:28px 0;flex-wrap:nowrap;padding:0 4px}.otp-input{width:45px;height:45px;border:2px solid #e5e7eb;border-radius:12px;text-align:center;font-size:18px;font-weight:bold;color:#374151;background:#fafafa;transition:all 0.3s ease;flex:0 0 45px;outline:none}.otp-input:focus{border-color:#3A8F9D;background:#fff;box-shadow:0 0 0 3px rgba(58,143,157,0.1);transform:scale(1.05)}.otp-input:not(:placeholder-shown){border-color:#10b981;background:#f0fdf4;color:#059669}.submit-button{width:100%;padding:16px;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);color:white;border:none;border-radius:14px;font-size:15px;font-weight:600;cursor:pointer;margin-bottom:20px;transition:all 0.3s ease;position:relative;overflow:hidden}.submit-button:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(46,75,123,0.4)}.submit-button:active{transform:translateY(0)}.submit-button:disabled{opacity:0.6;cursor:not-allowed;transform:none}.resend-section{text-align:center;padding:16px 0;border-top:1px solid rgba(229,231,235,0.6)}.resend-text{color:#64748b;font-size:13px;margin-bottom:8px;font-weight:500}.timer-badge{display:inline-flex;align-items:center;gap:4px;background:rgba(58,143,157,0.1);color:#3A8F9D;padding:4px 8px;border-radius:12px;font-weight:600;font-size:12px}.resend-button{color:#3A8F9D;background:none;border:none;font-weight:600;cursor:pointer;font-size:13px;transition:all 0.3s ease;padding:6px 12px;border-radius:8px;display:inline-flex;align-items:center;gap:4px}.resend-button:hover{color:#2E4B7B;background:rgba(46,75,123,0.1)}.back-section{text-align:center;margin-top:16px}.back-link{color:#3A8F9D;text-decoration:none;font-weight:600;font-size:13px;transition:all 0.3s ease;display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px}.back-link:hover{color:#2E4B7B;background:rgba(46,75,123,0.1)}.back-link i{font-size:11px}.debug-otp{background:linear-gradient(135deg,#fff3cd 0%,#ffeaa7 100%);border:2px solid #ffc107;border-radius:12px;padding:16px;margin-bottom:20px;text-align:center}.debug-otp h4{color:#856404;margin-bottom:6px;font-size:14px}.debug-otp .otp-code{font-size:24px;font-weight:bold;color:#2E4B7B;letter-spacing:3px;margin:8px 0;font-family:monospace}.debug-otp .debug-note{font-size:11px;color:#6c5700;font-style:italic}@media (max-width:480px){.otp-wrapper{padding:12px;max-width:100%}.otp-container{padding:32px 20px;border-radius:16px}.otp-icon{width:60px;height:60px;margin-bottom:16px}.otp-icon i{font-size:24px}.otp-title{font-size:22px}.otp-subtitle{font-size:13px}.otp-inputs{gap:6px;margin:24px 0;padding:0 2px}.otp-input{width:42px;height:42px;font-size:16px;border-radius:10px;flex:0 0 42px}.email-display{padding:10px 12px;font-size:12px;margin-bottom:20px}.submit-button{padding:14px;font-size:14px}.debug-otp .otp-code{font-size:20px;letter-spacing:2px}}@media (max-width:360px){.otp-inputs{gap:4px}.otp-input{width:38px;height:38px;font-size:15px;flex:0 0 38px}.debug-otp .otp-code{font-size:18px}}@media (min-width:481px) and (max-width:640px){.otp-inputs{gap:10px}.otp-input{width:48px;height:48px;font-size:20px;flex:0 0 48px}}@media (orientation:landscape) and (max-height:500px){.otp-container{padding:24px 30px}.otp-icon{width:50px;height:50px;margin-bottom:12px}.otp-title{font-size:20px;margin-bottom:4px}.otp-inputs{margin:20px 0}}</style>
</head>
<body>
    <div class="otp-wrapper">
        <div class="otp-container">
            <div class="otp-header">
                <div class="otp-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="otp-title">Verify OTP</h1>
                <p class="otp-subtitle">Enter the 6-digit verification code sent to your email</p>
            </div>

            <div class="email-display">
                <p>Code sent to: <strong>{{ $email }}</strong></p>
            </div>

            {{-- Debug OTP Display --}}
            @if(session('generated_otp') || isset($otp))
            {{-- <div class="debug-otp">
                <h4>🔑 Your OTP Code:</h4>
                <div class="otp-code">{{ session('generated_otp') ?? $otp ?? 'N/A' }}</div>
                <p class="debug-note">{{ session('debug_info') ?? 'Use this code if email is not delivered' }}</p>
            </div> --}}
            @endif

            <form method="POST" action="{{ route('admin.verify.otp.post') }}" id="otpForm">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="otp-inputs">
                    <input type="text" class="otp-input" maxlength="1" name="otp1" required inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code">
                    <input type="text" class="otp-input" maxlength="1" name="otp2" required inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" name="otp3" required inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" name="otp4" required inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" name="otp5" required inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" name="otp6" required inputmode="numeric" pattern="[0-9]">
                </div>

                <button type="submit" class="submit-button" id="submitBtn">
                    <i class="fas fa-check" style="margin-right: 6px;"></i>Verify OTP
                </button>
            </form>

            <div class="resend-section">
                <p class="resend-text">
                    Didn't receive the code?
                    <span class="timer-badge" id="timerBadge">
                        <i class="fas fa-clock"></i><span id="timer">60</span>s
                    </span>
                </p>
                <button type="button" id="resendBtn" class="resend-button" style="display: none;">
                    <i class="fas fa-refresh"></i>Resend OTP
                </button>
            </div>

            <div class="back-section">
                <a href="{{ route('admin.forgot.password') }}" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Forgot Password</span>
                </a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        toastr.options={closeButton:!0,newestOnTop:!0,progressBar:!0,positionClass:"toast-top-right",timeOut:5e3,showMethod:"fadeIn",hideMethod:"fadeOut"};@if(session('toastr'))@php $toastr=session('toastr');@endphp toastr.{{$toastr['type']}}('{{$toastr['message']}}');@endif

        // Enhanced OTP input handling
        function setupOTPInputs(){const inputs=$('.otp-input');inputs.on('input keyup',function(e){const value=this.value,index=inputs.index(this);if(1===value.length&&8!==e.keyCode&&index<inputs.length-1)inputs.eq(index+1).focus();if(8===e.keyCode&&0===value.length&&index>0)inputs.eq(index-1).focus();checkFormComplete()});inputs.on('keypress',function(e){const charCode=e.which||e.keyCode;(charCode<48||charCode>57)&&e.preventDefault()});inputs.on('paste',function(e){e.preventDefault();const paste=(e.originalEvent.clipboardData||window.clipboardData).getData('text'),digits=paste.replace(/\D/g,'').substring(0,6);for(let i=0;i<digits.length&&i<inputs.length;i++)inputs.eq(i).val(digits[i]);digits.length>0&&inputs.eq(Math.min(digits.length,inputs.length-1)).focus();checkFormComplete()})}

        function checkFormComplete(){const inputs=$('.otp-input'),allFilled=inputs.toArray().every(input=>1===input.value.length);$('#submitBtn').prop('disabled',!allFilled)}

        let timer=60;const countdown=setInterval(()=>{timer--,$('#timer').text(timer),timer<=0&&(clearInterval(countdown),$('#timerBadge').hide(),$('#resendBtn').show())},1e3);

        $('#resendBtn').on('click',function(){const email=$('input[name="email"]').val(),btn=$(this);btn.prop('disabled',!0).html('<i class="fas fa-spinner fa-spin"></i>Sending...'),$.post('{{route("admin.resend.otp")}}',{_token:'{{csrf_token()}}',email:email}).done(function(response){response.success?(toastr.success(response.message),response.otp&&$('.debug-otp .otp-code').text(response.otp),timer=60,$('#resendBtn').hide(),$('#timerBadge').show()):toastr.error(response.message)}).fail(function(){toastr.error('Failed to resend OTP. Please try again.')}).always(function(){btn.prop('disabled',!1).html('<i class="fas fa-refresh"></i>Resend OTP')})});

        $('#otpForm').on('submit',function(e){e.preventDefault();const inputs=$('.otp-input'),allFilled=inputs.toArray().every(input=>1===input.value.length);if(!allFilled)return toastr.error('Please enter all 6 digits'),inputs.filter(':empty').first().focus(),!1;const submitBtn=$('#submitBtn');submitBtn.prop('disabled',!0).html('<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Verifying...'),setTimeout(()=>{this.submit()},800)});

        $(document).ready(function(){setupOTPInputs(),$('.otp-input').first().focus(),checkFormComplete()});
    </script>
</body>
</html>
