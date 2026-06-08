<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PharmaControl\Auth\Domain\Exception\AccountLockedException;
use PharmaControl\Auth\Domain\Exception\InvalidCredentialsException;
use PharmaControl\Auth\Infrastructure\Middleware\Rbac2Middleware;
use PharmaControl\Shared\Exception\DomainException as SharedDomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rbac2' => Rbac2Middleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AccountLockedException $e, Request $request): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'ACCOUNT_LOCKED',
            ], 423);
        });

        $exceptions->render(function (InvalidCredentialsException $e, Request $request): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'INVALID_CREDENTIALS',
            ], 401);
        });

        $exceptions->render(function (SharedDomainException $e, Request $request): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'DOMAIN_ERROR',
            ], 422);
        });
    })->create();
