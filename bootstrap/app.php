<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HeaderKeamanan;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Mendaftarkan perintah artisan di app/Console/Commands (mis. admin:buat).
    // withRouting() hanya memuat routes/console.php, bukan direktori ini.
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        // Header keamanan HTTP untuk seluruh respons web (termasuk lampiran privat)
        $middleware->web(append: [
            HeaderKeamanan::class,
        ]);

        // Alias middleware pembatas peran: role:admin | role:cs
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
