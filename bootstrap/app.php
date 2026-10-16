<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'super_admin' => \App\Http\Middleware\RequireSuperAdmin::class,
            'ensure_password_changed' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
            'role' => \App\Http\Middleware\RequireRole::class,
            'verify_conversion_signature' => \App\Http\Middleware\VerifyConversionApiSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
