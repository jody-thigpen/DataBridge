<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\EnsurePlatformUser;
use App\Http\Middleware\SetOrganizationContext;
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
        $middleware->alias([
            'platform' => EnsurePlatformUser::class,
            'org.context' => SetOrganizationContext::class,
            'org.access' => EnsureOrganizationAccess::class,
        ]);

        $middleware->appendToGroup('web', SetOrganizationContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
