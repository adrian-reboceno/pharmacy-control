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
use Illuminate\Support\Facades\Route;

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
