<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP - DigiTransway Portal</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .email-header {
            background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 40px;
        }

        .email-title {
            font-size: 28px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .email-subtitle {
            font-size: 16px;
            margin: 0;
            opacity: 0.9;
        }

        .email-content {
            padding: 40px;
            text-align: center;
            line-height: 1.6;
        }

        .email-content h2 {
            color: #667eea;
            font-size: 24px;
            margin: 0 0 20px 0;
        }

        .email-content p {
            font-size: 16px;
            margin: 0 0 20px 0;
            color: #666;
        }

        .otp-code {
            font-size: 42px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            margin: 30px 0;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            border-radius: 15px;
            border: 2px solid #667eea;
            display: inline-block;
        }

        .warning-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            color: #856404;
            text-align: left;
        }

        .warning-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .email-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }

        .email-footer p {
            margin: 5px 0;
        }

        .footer-links {
            margin: 15px 0;
        }

        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 15px;
            }

            .email-header, .email-content {
                padding: 25px;
            }

            .otp-code {
                font-size: 32px;
                letter-spacing: 4px;
                padding: 20px;
            }

            .email-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="logo-container">👑</div>
            <h1 class="email-title">Password Reset Request</h1>
            <p class="email-subtitle">DigiTransway Luxury Admin Portal</p>
        </div>

        <div class="email-content">
            <h2>🔐 Your Security Code</h2>

            <p>Hello {{ $user->name }},</p>

            <p>You've requested to reset your password for the <strong>DigiTransway Admin Portal</strong>. For your security, please use the verification code below:</p>

            <div class="otp-code">{{ $otp }}</div>

            <div class="warning-box">
                <p><strong>⚠️ Important Security Information:</strong></p>
                <ul>
                    <li>This OTP will expire in <strong>10 minutes</strong></li>
                    <li>Never share this code with anyone</li>
                    <li>Our team will never ask for this code</li>
                    <li>Use this code only on the official DigiTransway portal</li>
                </ul>
            </div>

            <p>If you didn't request this password reset, please ignore this email or contact our security team immediately.</p>

            <p style="margin-top: 30px;">
                <strong>Need Help?</strong><br>
                Contact our support team at support@DigiTransway.com
            </p>

            <hr style="border: 1px solid #eee; margin: 30px 0;">

            <p style="font-size: 14px; color: #999;">
                This email was sent on {{ now()->format('F d, Y \a\t g:i A') }} IST<br>
                Request ID: {{ strtoupper(Str::random(8)) }}
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
