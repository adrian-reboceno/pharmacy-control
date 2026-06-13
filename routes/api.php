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
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ClassificationController;
use App\Http\Controllers\Catalog\LaboratoryController;
use App\Http\Controllers\Catalog\PresentationController;
use App\Http\Controllers\Catalog\RouteController;
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

Route::prefix('v1')->middleware(['rbac2:catalog.suppliers.manage'])->group(function (): void {
    Route::get('/suppliers',      [SupplierController::class, 'index']);
    Route::post('/suppliers',     [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
});
