<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimpleAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $contactNumber = $request->input('contact_number');
            $password = $request->input('password');

            if (!$contactNumber || !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact number and password required'
                ], 400);
            }

            // Pure SQL query
            $user = DB::selectOne("
                SELECT id, contact_number, name, email, password, is_verified, is_completed
                FROM users
                WHERE contact_number = ?
                LIMIT 1
            ", [$contactNumber]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!password_verify($password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            // Simple token generation
            $tokenData = [
                'user_id' => $user->id,
                'contact_number' => $user->contact_number,
                'issued_at' => time(),
                'expires_at' => time() + 3600
            ];

            $token = base64_encode(json_encode($tokenData));

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'user' => [
                    'id' => (int) $user->id,
                    'name' => $user->name ?? '',
                    'contact_number' => $user->contact_number,
                    'email' => $user->email ?? '',
                    'is_verified' => (bool) $user->is_verified,
                    'is_completed' => (bool) $user->is_completed
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
