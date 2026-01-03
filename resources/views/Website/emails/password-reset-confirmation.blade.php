<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful - DigiTransway Portal</title>
    <style>
        /* Same CSS as OTP template */
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 40px 20px; color: #333; }
        .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .email-header { background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%); padding: 40px; text-align: center; color: white; }
        .logo-container { width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 40px; }
        .email-title { font-size: 28px; font-weight: bold; margin: 0 0 10px 0; }
        .email-subtitle { font-size: 16px; margin: 0; opacity: 0.9; }
        .email-content { padding: 40px; text-align: center; line-height: 1.6; }
        .email-content h2 { color: #667eea; font-size: 24px; margin: 0 0 20px 0; }
        .email-content p { font-size: 16px; margin: 0 0 20px 0; color: #666; }
        .success-box { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; border-radius: 10px; padding: 20px; margin: 20px 0; color: #155724; }
        .button { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
        .warning-box { background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #ffc107; border-radius: 10px; padding: 20px; margin: 20px 0; color: #856404; text-align: left; }
        .warning-box ul { margin: 10px 0; padding-left: 20px; }
        .email-footer { background: #f8f9fa; padding: 30px; text-align: center; color: #6c757d; font-size: 14px; }
        .email-footer p { margin: 5px 0; }
        .footer-links { margin: 15px 0; }
        .footer-links a { color: #667eea; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="logo-container">👑</div>
            <h1 class="email-title">Password Reset Successful</h1>
            <p class="email-subtitle">Your Account is Secure</p>
        </div>

        <div class="email-content">
            <h2>✅ Password Successfully Updated</h2>

            <p>Hello {{ $user->name }},</p>

            <p>Great news! Your password for the <strong>DigiTransway Admin Portal</strong> has been successfully reset and updated.</p>

            <div class="success-box">
                <p><strong>🎉 Password Reset Complete!</strong></p>
                <p>Your account is now secured with your new password. You can now log in using your updated credentials.</p>
            </div>

            <a href="{{ route('admin.login') }}" class="button">
                🚀 Login to Your Account
            </a>

            <div class="warning-box">
                <p><strong>🛡️ Security Reminder:</strong></p>
                <ul>
                    <li>Keep your password secure and don't share it</li>
                    <li>Use a strong, unique password</li>
                    <li>Enable two-factor authentication when available</li>
                    <li>Log out from shared or public computers</li>
                </ul>
            </div>

            <p><strong>Didn't reset your password?</strong></p>
            <p>If you didn't perform this action, please contact our security team immediately at <strong>security@DigiTransway.com</strong> or call our emergency hotline.</p>

            <hr style="border: 1px solid #eee; margin: 30px 0;">

            <p style="font-size: 14px; color: #999;">
                Password reset completed on {{ now()->format('F d, Y \a\t g:i A') }} IST<br>
                Security ID: {{ strtoupper(Str::random(10)) }}
            </p>
        </div>

        <div class="email-footer">
            <p><strong>&copy; {{ date('Y') }} DigiTransway Admin Portal. All rights reserved.</strong></p>
            <p>This is a secure, automated message from our system.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Support</a>
            </div>
            <p style="font-size: 12px; margin-top: 20px;">
                If you didn't request this email, please ignore it or contact our support team.
            </p>
        </div>
    </div>
</body>
</html>
