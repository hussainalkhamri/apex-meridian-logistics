<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'auto.insights' => \App\Http\Middleware\AutoGenerateSystemInsights::class,
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'customer.auth' => \App\Http\Middleware\AuthenticateCustomer::class,
        ]);

        // Apply SetLocale after session but before other middleware
        $middleware->append(\App\Http\Middleware\SetLocale::class);
        $middleware->append(\App\Http\Middleware\LocalizeStaticHtml::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->validateCsrfTokens();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
