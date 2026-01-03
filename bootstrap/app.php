<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\AdminAuthMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '/api', // ✅ API prefix
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ Middleware aliases
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'admin.auth' => AdminAuthMiddleware::class, // ✅ Admin auth middleware
        ]);

        // ✅ API middleware group mein ForceJsonResponse add karo
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ✅ API exception handling
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error_code' => 'API_ERROR',
                    'error_type' => get_class($e),
                    'timestamp' => now()->toDateTimeString()
                ], 500);
            }
        });
    })
    ->create();
