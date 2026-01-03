<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('admin_logged_in')) {
            return redirect()->route('admin.login')->with('toastr', [
                'type' => 'warning',
                'message' => 'Please login to access this page!'
            ]);
        }

        return $next($request);
    }
}
