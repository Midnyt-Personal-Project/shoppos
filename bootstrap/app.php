<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\{LicenseMiddleware, RoleMiddleware, SetCurrentBranch};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        then: function () {

            Route::middleware('api')
                ->prefix('external')
                ->group(base_path('routes/external.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'    => RoleMiddleware::class,
            'license' => LicenseMiddleware::class,
            'branch'  => SetCurrentBranch::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
