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
    ->withMiddleware(function (Middleware $middleware) {
        // Registering your custom middleware aliases
        $middleware->alias([
            'truck.approved' => \App\Http\Middleware\CheckTruckStatus::class,
            'admin'          => \App\Http\Middleware\Admin::class,
            'ftadmin.status' => \App\Http\Middleware\CheckFtadminStatus::class,
            'ftworker.status' => \App\Http\Middleware\CheckFtworkerStatus::class,
            'role'           => \App\Http\Middleware\EnsureUserRole::class,
        ]);

        // Enforce pending ftadmin lockout across all web routes
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckFtadminStatus::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();