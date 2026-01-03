<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminProfileController extends Controller
{
    public function profile()
    {
        $pageTitle = 'Admin Profile';
        $adminData = [
            'name' => Session::get('admin_name', 'Admin'),
            'email' => Session::get('admin_email', 'admin@admin.com'),
            'role' => 'Super Administrator'
        ];

        return view('Website.admin.profile', compact('pageTitle', 'adminData'));
    }

    public function updateProfile(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('profile_error', 'Please fix the validation errors!');
        }

        // Update session data (In real app, update database)
        Session::put('admin_name', $request->name);
        Session::put('admin_email', $request->email);

        return back()->with('profile_success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
            'new_password_confirmation' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('password_error', 'Please fix the validation errors!');
        }

        // Check if current password is correct (hardcoded for demo)
        if ($request->current_password !== '123456') {
            return back()->with('password_error', 'Current password is incorrect!');
        }

        // In real app, you would update the database here
        // For demo purposes, we'll just show success message

        return back()->with('password_success', 'Password changed successfully! Please login again with your new password.');
    }

    public function settings()
    {
        $pageTitle = 'Admin Settings';

        return view('Website.admin.settings', compact('pageTitle'));
    }

    public function updateSettings(Request $request)
    {
        // Handle settings update
        return back()->with('settings_success', 'Settings updated successfully!');
    }

    public function updateAppearance(Request $request)
    {
        // Handle appearance update
        return back()->with('settings_success', 'Appearance settings updated!');
    }

    public function updateNotifications(Request $request)
    {
        // Handle notifications update
        return back()->with('settings_success', 'Notification preferences updated!');
    }

    public function updateSecurity(Request $request)
    {
        // Handle security update
        return back()->with('settings_success', 'Security settings updated!');
    }
}
