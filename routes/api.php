<?php

// ── ARCHIVO: routes/api.php ──
declare(strict_types=1);

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\SwitchRoleController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Catalog\ActiveIngredientController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ClassificationController;
use App\Http\Controllers\Catalog\LaboratoryController;
use App\Http\Controllers\Catalog\LocationController;
use App\Http\Controllers\Catalog\PresentationController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\RouteController;
use App\Http\Controllers\Catalog\StatusController;
use App\Http\Controllers\Catalog\UnitOfMeasurementController;
use App\Http\Controllers\Suppliers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/docs', function () {
    return view('api-docs');
});

Route::prefix('v1/auth')->group(function (): void {

    // Public endpoints
    Route::post('/login', LoginController::class);
    Route::post('/refresh', RefreshTokenController::class);

    // Authenticated endpoints
    Route::middleware('rbac2')->group(function (): void {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', [UserController::class, 'me']);

        Route::post('/change-password', ChangePasswordController::class);
        Route::post('/switch-role', SwitchRoleController::class);

        // 2FA
        Route::post('/2fa/setup', [TwoFactorController::class, 'setup']);
        Route::post('/2fa/verify', [TwoFactorController::class, 'verify']);

        // User management
        Route::post('/users', [UserController::class, 'store']);
        Route::post('/users/{userId}/roles', [RoleController::class, 'assign']);
        Route::delete('/users/{userId}/roles/{roleId}', [RoleController::class, 'revoke']);
        Route::post('/users/{userId}/unlock', [UserController::class, 'unlock']);
    });
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.active-ingredients.manage'])->group(function (): void {
    Route::get('/active-ingredients', [ActiveIngredientController::class, 'index']);
    Route::post('/active-ingredients', [ActiveIngredientController::class, 'store']);
    Route::get('/active-ingredients/{id}', [ActiveIngredientController::class, 'show']);
    Route::put('/active-ingredients/{id}', [ActiveIngredientController::class, 'update']);
    Route::delete('/active-ingredients/{id}', [ActiveIngredientController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.laboratories.manage'])->group(function (): void {
    Route::get('/laboratories', [LaboratoryController::class, 'index']);
    Route::post('/laboratories', [LaboratoryController::class, 'store']);
    Route::get('/laboratories/{id}', [LaboratoryController::class, 'show']);
    Route::put('/laboratories/{id}', [LaboratoryController::class, 'update']);
    Route::delete('/laboratories/{id}', [LaboratoryController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.categories.manage'])->group(function (): void {
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.classifications.manage'])->group(function (): void {
    Route::get('/classifications', [ClassificationController::class, 'index']);
    Route::get('/classifications/{id}', [ClassificationController::class, 'show']);
    Route::put('/classifications/{id}', [ClassificationController::class, 'update']);
    Route::delete('/classifications/{id}', [ClassificationController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.units.manage'])->group(function (): void {
    Route::get('/units', [UnitOfMeasurementController::class, 'index']);
    Route::post('/units', [UnitOfMeasurementController::class, 'store']);
    Route::get('/units/{id}', [UnitOfMeasurementController::class, 'show']);
    Route::put('/units/{id}', [UnitOfMeasurementController::class, 'update']);
    Route::delete('/units/{id}', [UnitOfMeasurementController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.presentations.manage'])->group(function (): void {
    Route::get('/presentations', [PresentationController::class, 'index']);
    Route::post('/presentations', [PresentationController::class, 'store']);
    Route::get('/presentations/{id}', [PresentationController::class, 'show']);
    Route::put('/presentations/{id}', [PresentationController::class, 'update']);
    Route::delete('/presentations/{id}', [PresentationController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.routes-of-administration.manage'])->group(function (): void {
    Route::get('/routes-of-administration', [RouteController::class, 'index']);
    Route::post('/routes-of-administration', [RouteController::class, 'store']);
    Route::get('/routes-of-administration/{id}', [RouteController::class, 'show']);
    Route::put('/routes-of-administration/{id}', [RouteController::class, 'update']);
    Route::delete('/routes-of-administration/{id}', [RouteController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.statuses.manage'])->group(function (): void {
    Route::get('/statuses', [StatusController::class, 'index']);
    Route::post('/statuses', [StatusController::class, 'store']);
    Route::get('/statuses/{id}', [StatusController::class, 'show']);
    Route::put('/statuses/{id}', [StatusController::class, 'update']);
    Route::delete('/statuses/{id}', [StatusController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.locations.manage'])->group(function (): void {
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/tree', [LocationController::class, 'tree']);
    Route::get('/locations/leaves', [LocationController::class, 'leaves']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::get('/locations/{id}', [LocationController::class, 'show']);
    Route::put('/locations/{id}', [LocationController::class, 'update']);
    Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
});

Route::prefix('v1/catalog')->middleware(['rbac2:catalog.products.manage'])->group(function (): void {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::post('/products/{id}/images', [ProductController::class, 'addImage']);
    Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'removeImage']);
    Route::put('/products/{id}/images/reorder', [ProductController::class, 'reorderImages']);
});

Route::prefix('v1')->middleware(['rbac2:catalog.suppliers.manage'])->group(function (): void {
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
});
