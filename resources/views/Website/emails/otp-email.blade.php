@extends('Website.emails.layouts.master')

@section('title', 'Password Reset OTP - DigiTransway Portal')
@section('email-title', 'Password Reset Request')
@section('email-subtitle', 'Secure OTP Verification')

@section('content')
    <h2>🔐 Your Security Code</h2>

    <p>Hello Admin,</p>

    <p>You've requested to reset your password for the <strong>DigiTransway Admin Portal</strong>. For your security, please use the verification code below:</p>

    <div class="otp-code">{{ $otp }}</div>

    <div class="warning-box">
        <p><strong>⚠️ Important Security Information:</strong></p>
        <ul style="text-align: left; margin: 10px 0;">
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
@endsection
