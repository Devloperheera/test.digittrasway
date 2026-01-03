<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', 'DigiTransway Admin Portal')</title>
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        /* Email styles */
        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
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
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.3);
        }

        .logo-icon {
            font-size: 40px;
            color: white;
            text-shadow: 0 0 20px rgba(255,255,255,0.5);
        }

        .email-title {
            font-size: 28px;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-shadow: 0 0 30px rgba(255,255,255,0.3);
        }

        .email-subtitle {
            font-size: 16px;
            margin: 0;
            opacity: 0.9;
            text-shadow: 0 0 10px rgba(255,255,255,0.2);
        }

        .email-content {
            padding: 40px;
            text-align: center;
            color: #333;
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
            text-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
            display: inline-block;
        }

        .button {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .warning-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            color: #856404;
        }

        .success-box {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            color: #155724;
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

        /* Responsive */
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
    <div style="padding: 40px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="email-container">
            <div class="email-header">
                <div class="logo-container">
                    <div class="logo-icon">👑</div>
                </div>
                <h1 class="email-title">@yield('email-title', 'DigiTransway Admin Portal')</h1>
                <p class="email-subtitle">@yield('email-subtitle', 'Luxury Admin Experience')</p>
            </div>

            <div class="email-content">
                @yield('content')
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
    </div>
</body>
</html>
