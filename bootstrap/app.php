<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use PharmaControl\Auth\Domain\Exception\AccountLockedException;
use PharmaControl\Auth\Domain\Exception\InvalidCredentialsException;
use PharmaControl\Auth\Infrastructure\Middleware\Rbac2Middleware;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateCasNumberException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateDciCodeException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\DuplicateIngredientNameException;
use PharmaControl\Catalog\ActiveIngredient\Domain\Exception\IngredientNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryCycleException;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Exception\DuplicateCategorySlugException;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotFoundException;
use PharmaControl\Catalog\Classifications\Domain\Exception\ClassificationNotModifiableException;
use PharmaControl\Catalog\Laboratories\Domain\Exception\DuplicateLaboratoryNameException;
use PharmaControl\Catalog\Laboratories\Domain\Exception\LaboratoryNotFoundException;
use PharmaControl\Catalog\Location\Domain\Exception\DuplicateLocationNameException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationMaxDepthException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotFoundException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotLeafException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicateAbbreviationException;
use PharmaControl\Catalog\Presentations\Domain\Exception\DuplicatePresentationNameException;
use PharmaControl\Catalog\Presentations\Domain\Exception\PresentationNotFoundException;
use PharmaControl\Catalog\Products\Domain\Exception\BrandedProductRequiresLaboratoryException;
use PharmaControl\Catalog\Products\Domain\Exception\DuplicateBarcodeException;
use PharmaControl\Catalog\Products\Domain\Exception\DuplicateProductNameException;
use PharmaControl\Catalog\Products\Domain\Exception\InvalidBarcodeException;
use PharmaControl\Catalog\Products\Domain\Exception\LocationMustBePositionException;
use PharmaControl\Catalog\Products\Domain\Exception\ProductImageNotFoundException;
use PharmaControl\Catalog\Products\Domain\Exception\ProductNotFoundException;
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
    ->withEvents(discover: false)
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'rbac2' => Rbac2Middleware::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => null);

        // ── COOKIES ──────────────────────────────────────────────────────────
        // Excluir las cookies de token del encriptado automático de Laravel.
        // El backend las emite como JWTs firmados — si Laravel las encriptara,
        // el Rbac2Middleware no podría leer el valor real al verificar la firma.
        $middleware->encryptCookies([
            'access_token',
            'refresh_token',
        ]);

        // ── CORS ─────────────────────────────────────────────────────────────
        // withCredentials: true en Angular requiere:
        //   1. Access-Control-Allow-Origin con origen exacto (no *)
        //   2. Access-Control-Allow-Credentials: true
        // Esto se configura en config/cors.php (ver abajo).
        // Aquí solo aseguramos que el middleware HandleCors esté en el grupo api.
        $middleware->api(prepend: [
            HandleCors::class,
        ]);
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
        $exceptions->render(function (IngredientNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateIngredientNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (DuplicateDciCodeException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (DuplicateCasNumberException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (LocationNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateLocationNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (LocationMaxDepthException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'INVALID_LEVEL'], 422);
        });
        $exceptions->render(function (LocationNotLeafException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'NOT_A_POSITION'], 422);
        });
        $exceptions->render(function (ProductImageNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (ProductNotFoundException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (DuplicateProductNameException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (DuplicateBarcodeException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage()], 409);
        });
        $exceptions->render(function (InvalidBarcodeException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'INVALID_BARCODE'], 422);
        });
        $exceptions->render(function (BrandedProductRequiresLaboratoryException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'LABORATORY_REQUIRED'], 422);
        });
        $exceptions->render(function (LocationMustBePositionException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'LOCATION_NOT_POSITION'], 422);
        });
        $exceptions->render(function (SharedDomainException $e, Request $request): JsonResponse {
            return response()->json(['message' => $e->getMessage(), 'error' => 'DOMAIN_ERROR'], 422);
        });
    })->create();
