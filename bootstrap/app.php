<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\EnsurePlatformUser;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetOrganizationContext;
use App\Http\Middleware\SetTenantContext;
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
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'platform' => EnsurePlatformUser::class,
            'org.context' => SetOrganizationContext::class,
            'org.access' => EnsureOrganizationAccess::class,
        ]);

        $middleware->appendToGroup('web', SetTenantContext::class);
        $middleware->appendToGroup('web', SetOrganizationContext::class);
        $middleware->appendToGroup('web', SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
