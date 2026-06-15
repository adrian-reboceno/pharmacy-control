<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PharmaControl\Auth\Domain\Exception\AccountLockedException;
use PharmaControl\Auth\Domain\Exception\InvalidCredentialsException;
use PharmaControl\Auth\Infrastructure\Middleware\Rbac2Middleware;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryCycleException;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Exception\DuplicateCategorySlugException;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotFoundException;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotModifiableException;
use PharmaControl\Catalog\Laboratories\Domain\Exception\DuplicateLaboratoryNameException;
use PharmaControl\Catalog\Laboratories\Domain\Exception\LaboratoryNotFoundException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicateAbbreviationException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicatePresentationNameException;
use PharmaControl\Catalog\Presentations\Domain\Exception\PresentationNotFoundException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteCodeException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\DuplicateRouteNameException;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Exception\RouteNotFoundException;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusCodeException;
use PharmaControl\Catalog\Status\Domain\Exception\DuplicateStatusNameException;
use PharmaControl\Catalog\Status\Domain\Exception\StatusNotFoundException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitNameException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitSymbolException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\UnitNotFoundException;
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
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request): JsonResponse {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });
        $exceptions->render(function (AccountLockedException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'ACCOUNT_LOCKED'], 423);
        });
        $exceptions->render(function (InvalidCredentialsException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'INVALID_CREDENTIALS'], 401);
        });
        $exceptions->render(function (CategoryNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateCategorySlugException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (CategoryCycleException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'CYCLE_DETECTED'], 422);
        });
        $exceptions->render(function (LaboratoryNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateLaboratoryNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (ClassificationNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (ClassificationNotModifiableException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'NOT_MODIFIABLE'], 422);
        });
        $exceptions->render(function (UnitNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateUnitNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (DuplicateUnitSymbolException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (PresentationNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicatePresentationNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (DuplicateAbbreviationException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (RouteNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateRouteNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (DuplicateRouteCodeException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (StatusNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateStatusNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (DuplicateStatusCodeException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (SharedDomainException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'DOMAIN_ERROR'], 422);
        });
    })->create();
