<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ValidateCustomToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], 401);
            }

            // Decode and validate token
            $tokenData = base64_decode($token);
            $parts = explode(':', $tokenData);

            if (count($parts) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 401);
            }

            $userId = $parts[0];
            $timestamp = $parts[1];

            // Check expiry
            if ((time() - $timestamp) > 3600) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired'
                ], 401);
            }

            // Verify user exists
            $user = DB::selectOne("SELECT id FROM users WHERE id = ?", [$userId]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            // Add user ID to request
            $request->merge(['authenticated_user_id' => $userId]);

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token validation failed'
            ], 401);
        }
    }
}
