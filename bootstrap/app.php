<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.active' => \App\Http\Middleware\EnsureAccountIsActive::class,
            'platform.staff' => \App\Http\Middleware\EnsureAdminAccess::class,
        ]);
    })
      ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A request that is authorized but breaks a business rule is a 422, not a 500.
        $exceptions->render(function (\App\Exceptions\BusinessRuleException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
    })->create();
