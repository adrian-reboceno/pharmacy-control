<?php

// ── ARCHIVO: app/Http/Controllers/Auth/LogoutController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\LogoutController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/logout', middleware: ['auth:sanctum'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var AuthenticatedUser $user */
        $user = $request->attributes->get('authenticated_user');

        ($this->controller)([
            'user_id' => $user->userId,
            'session_id' => $user->sessionId,
            'jti' => $user->jti,
            'token_ttl_remaining' => $user->tokenTtlRemaining(),
        ]);

        return response()->json(['message' => 'Sesión cerrada exitosamente.'], 200);
    }
}
