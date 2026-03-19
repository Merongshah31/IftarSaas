<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\Handler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register tenant middleware
        $middleware->alias([
            'tenant' => \App\Http\Middleware\SetTenantContext::class,
            'admin.auth' => \App\Http\Middleware\AuthenticateAdminApi::class,
            'master.key' => \App\Http\Middleware\AuthenticateMasterKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handler is registered via App\Exceptions\Handler
    })->create();
