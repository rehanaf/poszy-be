<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // SPA
        // $middleware->statefulApi();

        // Role Middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckUserRole::class,
            'store.context' => \App\Http\Middleware\SetStoreContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Ini adalah tempat Anda menangani exception.
        $exceptions->render(function (AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) { // Hanya berlaku untuk rute API
                return response()->json([
                    'message' => '401 Unauthorized, you dont have access to this resource.'
                ], 401);
            }
        });
    })->create();