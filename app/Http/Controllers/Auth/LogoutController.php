<?php

// ── ARCHIVO: app/Http/Controllers/Auth/LogoutController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use OpenApi\Attributes as OA;
use PharmaControl\Auth\Infrastructure\Controller\LogoutController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/logout', middleware: ['auth:sanctum'])]
    #[OA\Post(
        path: '/v1/auth/logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el token actual, invalida la sesión y borra las cookies HttpOnly.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
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

        // Borrar cookies — se sobrescriben con valores vacíos y expiración
        // en el pasado. El browser las elimina automáticamente.
        $secure = config('app.env') === 'production';

        $clearAccess = cookie(
            name: 'access_token',
            value: '',
            minutes: -1,
            path: '/',
            domain: null,
            secure: $secure,
            httpOnly: true,
        );

        $clearRefresh = cookie(
            name: 'refresh_token',
            value: '',
            minutes: -1,
            path: '/api/v1/auth/refresh',
            domain: null,
            secure: $secure,
            httpOnly: true,
        );

        return response()
            ->json(['message' => 'Sesión cerrada exitosamente.'], 200)
            ->withCookie($clearAccess)
            ->withCookie($clearRefresh);
    }
}
