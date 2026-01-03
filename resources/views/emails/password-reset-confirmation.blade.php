<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset Successful</title>
    <style>body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px}.container{max-width:600px;margin:0 auto;background:white;border-radius:8px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1)}.header{background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);color:white;padding:30px;text-align:center}.header h1{margin:0;font-size:24px}.content{padding:30px}.success{background:#d4edda;color:#155724;padding:15px;border-radius:8px;margin:20px 0}.button{display:inline-block;background:linear-gradient(135deg,#2E4B7B 0%,#3A8F9D 100%);color:white;padding:12px 24px;text-decoration:none;border-radius:6px;margin:20px 0}.footer{background:#f4f4f4;padding:20px;text-align:center;color:#666;font-size:14px}</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Successful</h1>
            <p>Your Account is Secure</p>
        </div>
        <div class="content">
            <p>Hello {{ $user->name ?? 'Admin' }},</p>
            <div class="success">
                <strong>✅ Password Successfully Updated!</strong>
                <p>Your password has been reset successfully.</p>
            </div>
            <p>You can now login with your new password.</p>
            <a href="{{ route('admin.login') }}" class="button">Login to Account</a>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Digit Transway. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
