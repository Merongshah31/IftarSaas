<?php


namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // ...existing code...

    /**
     * The application's route middleware.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        // ...existing middleware...
        'tenant' => \App\Http\Middleware\SetTenantContext::class,  // ← Add this
    ];
}