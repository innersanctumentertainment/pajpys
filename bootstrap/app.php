<?php

use App\Http\Middleware\CheckStaffPermission;
use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureVaApproved;
use App\Http\Middleware\RateLimitAuth;
use App\Http\Middleware\SetActiveRole;
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
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'verified.email' => EnsureEmailVerified::class,
            'va.approved' => EnsureVaApproved::class,
            'active.role' => SetActiveRole::class,
            'staff.permission' => CheckStaffPermission::class,
            'rate.auth' => RateLimitAuth::class,
            'role' => EnsureRole::class,
            'permission' => EnsurePermission::class,
        ]);

        $middleware->web(append: [
            SetActiveRole::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
