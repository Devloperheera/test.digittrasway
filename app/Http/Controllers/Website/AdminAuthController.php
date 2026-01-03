<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Mail\OTPResetPassword;
use App\Mail\PasswordResetConfirmation;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Session::has('admin_logged_in')) {
            return redirect()->route('Website.home');
        }

        return view('Website.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        try {
            $admin = Admin::where('email', $request->email)->first();

            if ($admin && Hash::check($request->password, $admin->password)) {
                Session::put('admin_logged_in', true);
                Session::put('admin_id', $admin->id);
                Session::put('admin_name', $admin->name);
                Session::put('admin_email', $admin->email);

                if ($request->remember) {
                    config(['session.lifetime' => 525600]);
                }

                return redirect()->route('Website.home')->with('toastr', [
                    'type' => 'success',
                    'message' => 'Welcome back, ' . $admin->name . '!'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
        }

        return back()->with('toastr', [
            'type' => 'error',
            'message' => 'Invalid email or password!'
        ])->withInput();
    }

    public function showForgotPassword()
    {
        return view('Website.auth.forgot-password');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return back()->with('toastr', [
                'type' => 'error',
                'message' => 'Email not found in our records!'
            ])->withInput();
        }

        $otp = rand(100000, 999999);

        // Store OTP in session
        Session::put('reset_email', $request->email);
        Session::put('reset_otp', $otp);
        Session::put('otp_created_at', now());
        Session::put('reset_admin_id', $admin->id);
        Session::put('generated_otp', $otp);

        // ✅ Enhanced Gmail SMTP configuration
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.encryption' => 'tls',
            'mail.mailers.smtp.username' => '25gtconnect@gmail.com',
            'mail.mailers.smtp.password' => env('MAIL_PASSWORD'), // ✅ App Password
            'mail.mailers.smtp.timeout' => 60,
            'mail.mailers.smtp.auth_mode' => 'login',
            'mail.from.address' => '25gtconnect@gmail.com',
            'mail.from.name' => 'DigiTransway',
        ]);

        // Clear mail manager cache
        app('mail.manager')->forgetMailers();

        // ✅ Send email with corrected exception handling
        try {
            $userData = (object)[
                'name' => $admin->name ?? 'Admin',
                'email' => $admin->email
            ];

            Log::info('Sending OTP to: ' . $request->email);
            Log::info('SMTP Config: ' . config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port'));
            Log::info('Username: ' . config('mail.mailers.smtp.username'));

            Mail::to($request->email)->send(new OTPResetPassword($otp, $userData));

            Log::info('✅ OTP Email sent successfully!');

            return redirect()->route('admin.verify.otp', ['email' => $request->email])
                ->with('toastr', [
                    'type' => 'success',
                    'message' => 'OTP sent to your email successfully!'
                ]);

        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            // ✅ Laravel 11 compatible exception
            Log::error('SMTP Transport Error: ' . $e->getMessage());
            return $this->handleMailError($request->email, $otp, 'SMTP Transport Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('General Mail Error: ' . $e->getMessage());
            Log::error('Error Class: ' . get_class($e));
            return $this->handleMailError($request->email, $otp, 'Mail Error: ' . $e->getMessage());
        }
    }

    // ✅ Helper method for mail error handling
    private function handleMailError($email, $otp, $errorMessage)
    {
        Log::error('Mail Error Details: ' . $errorMessage);

        return redirect()->route('admin.verify.otp', ['email' => $email])
            ->with('toastr', [
                'type' => 'warning',
                'message' => 'Your OTP: ' . $otp . ' (Email delivery failed)'
            ])
            ->with('debug_info', $errorMessage);
    }

    public function showVerifyOTP($email)
    {
        if (!Session::get('reset_email') || !Session::get('reset_otp')) {
            return redirect()->route('admin.forgot.password')->with('toastr', [
                'type' => 'error',
                'message' => 'Please request OTP first.'
            ]);
        }

        // Check OTP expiry (10 minutes)
        if (Session::get('otp_created_at') && Session::get('otp_created_at')->diffInMinutes(now()) > 10) {
            Session::forget(['reset_email', 'reset_otp', 'otp_created_at', 'reset_admin_id', 'generated_otp']);
            return redirect()->route('admin.forgot.password')->with('toastr', [
                'type' => 'error',
                'message' => 'OTP expired. Please request a new one.'
            ]);
        }

        $otp = Session::get('generated_otp', Session::get('reset_otp'));
        return view('Website.auth.verify-otp', compact('email', 'otp'));
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp1' => 'required|digits:1',
            'otp2' => 'required|digits:1',
            'otp3' => 'required|digits:1',
            'otp4' => 'required|digits:1',
            'otp5' => 'required|digits:1',
            'otp6' => 'required|digits:1',
        ]);

        $otp = $request->otp1 . $request->otp2 . $request->otp3 .
               $request->otp4 . $request->otp5 . $request->otp6;

        if (!Session::get('reset_otp') || Session::get('reset_otp') != $otp) {
            return back()->with('toastr', [
                'type' => 'error',
                'message' => 'Invalid OTP. Please check and try again.'
            ])->withInput();
        }

        // Check OTP expiry
        if (Session::get('otp_created_at') && Session::get('otp_created_at')->diffInMinutes(now()) > 10) {
            Session::forget(['reset_email', 'reset_otp', 'otp_created_at', 'reset_admin_id', 'generated_otp']);
            return redirect()->route('admin.forgot.password')->with('toastr', [
                'type' => 'error',
                'message' => 'OTP expired. Please request a new one.'
            ]);
        }

        $token = Str::random(60);
        Session::put('reset_token', $token);

        return redirect()->route('admin.reset.password', [
            'token' => $token,
            'email' => Session::get('reset_email')
        ])->with('toastr', [
            'type' => 'success',
            'message' => 'OTP verified successfully!'
        ]);
    }

    public function resendOTP(Request $request)
    {
        $email = $request->get('email');
        $admin = Admin::where('email', $email)->first();

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Admin not found']);
        }

        $otp = rand(100000, 999999);

        Session::put('reset_otp', $otp);
        Session::put('otp_created_at', now());
        Session::put('generated_otp', $otp);

        try {
            $userData = (object)[
                'name' => $admin->name ?? 'Admin',
                'email' => $admin->email
            ];

            Mail::to($email)->send(new OTPResetPassword($otp, $userData));

            return response()->json([
                'success' => true,
                'message' => 'New OTP sent successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'otp' => $otp,
                'message' => 'New OTP generated: ' . $otp . ' (Email may have failed)'
            ]);
        }
    }

    public function showResetPassword($token, $email)
    {
        if (!Session::get('reset_email') || Session::get('reset_email') !== $email) {
            return redirect()->route('admin.forgot.password')->with('toastr', [
                'type' => 'error',
                'message' => 'Please complete OTP verification first.'
            ]);
        }

        if (!Session::has('reset_token') || Session::get('reset_token') !== $token) {
            return redirect()->route('admin.forgot.password')->with('toastr', [
                'type' => 'error',
                'message' => 'Invalid reset token.'
            ]);
        }

        return view('Website.auth.reset-password', compact('token', 'email'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required|min:8',
            'token' => 'required'
        ]);

        if (!Session::get('reset_email') || Session::get('reset_email') !== $request->email) {
            return redirect()->route('admin.forgot.password')->with('toastr', [
                'type' => 'error',
                'message' => 'Invalid password reset session.'
            ]);
        }

        if (!Session::has('reset_token') || Session::get('reset_token') !== $request->token) {
            return back()->with('toastr', [
                'type' => 'error',
                'message' => 'Invalid reset token.'
            ]);
        }

        try {
            $updated = Admin::where('email', $request->email)->update([
                'password' => Hash::make($request->password),
                'remember_token' => null,
                'updated_at' => now()
            ]);

            if (!$updated) {
                return back()->with('toastr', [
                    'type' => 'error',
                    'message' => 'Failed to update password. Please try again.'
                ]);
            }

            Session::forget(['reset_email', 'reset_otp', 'otp_created_at', 'reset_admin_id', 'generated_otp', 'reset_token']);

            return redirect()->route('admin.login')->with('toastr', [
                'type' => 'success',
                'message' => 'Password reset successfully! Please login with your new password.'
            ]);
        } catch (\Exception $e) {
            return back()->with('toastr', [
                'type' => 'error',
                'message' => 'Failed to reset password. Please try again.'
            ]);
        }
    }

    public function logout()
    {
        Session::flush();

        return redirect()->route('admin.login')->with('toastr', [
            'type' => 'success',
            'message' => 'You have been logged out successfully!'
        ]);
    }
}
