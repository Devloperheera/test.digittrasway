<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP - DigiTransway</title>
    <style>
        body{margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4}
        .email-container{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
        .header{background:linear-gradient(135deg,#3E6493 0%,#298C91 100%);padding:30px 20px;text-align:center}
        .logo{width:60px;height:60px;background:#fff;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:15px}
        .header h1{color:#fff;margin:0;font-size:24px;font-weight:600}
        .content{padding:40px 30px}
        .greeting{font-size:18px;color:#333;margin-bottom:15px}
        .message{color:#666;line-height:1.6;margin-bottom:30px}
        .otp-section{background:#f8fafc;border:2px dashed #3E6493;border-radius:12px;padding:25px;text-align:center;margin:30px 0}
        .otp-label{font-size:14px;color:#666;margin-bottom:10px;text-transform:uppercase;letter-spacing:1px}
        .otp-code{font-size:32px;font-weight:bold;color:#3E6493;letter-spacing:8px;margin:15px 0}
        .otp-note{font-size:12px;color:#888;margin-top:15px}
        .warning{background:#fff3cd;border:1px solid #ffeaa7;border-radius:8px;padding:15px;margin:25px 0;color:#856404}
        .footer{background:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #e9ecef}
        .footer p{margin:0;font-size:12px;color:#666}
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">🚛</div>
            <h1>DigiTransway Admin Portal</h1>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $user->name }},</div>

            <div class="message">
                We received a request to reset your admin account password. Please use the following OTP:
            </div>

            <div class="otp-section">
                <div class="otp-label">Your OTP Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-note">This code expires in 10 minutes</div>
            </div>

            <div class="warning">
                <strong>⚠️ Security Notice:</strong> If you did not request this password reset, please ignore this email and contact support immediately.
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2025 DigiTransway. All rights reserved.</p>
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
