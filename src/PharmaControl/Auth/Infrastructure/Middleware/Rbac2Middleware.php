<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Middleware/Rbac2Middleware.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PharmaControl\Auth\Application\DTO\TokenPayload;
use PharmaControl\Auth\Domain\Contract\Service\TokenServiceContract;

final class Rbac2Middleware
{
    private const INACTIVITY_THRESHOLD = 3600;

    public function __construct(
        private readonly TokenServiceContract $tokenService,
    ) {}

    public function handle(Request $request, Closure $next, string $requiredPermission = ''): mixed
    {
        $rawToken = $this->extractToken($request);

        if ($rawToken === null) {
            return $this->unauthorized('Token de autorización requerido.');
        }

        try {
            $claims = $this->tokenService->verify($rawToken);
        } catch (\Throwable) {
            return $this->unauthorized('Token inválido o expirado.');
        }

        $payload = TokenPayload::fromArray($claims);

        if ($this->tokenService->isBlacklisted($payload->jti)) {
            return $this->unauthorized('Token revocado.');
        }

        if ($payload->isExpired()) {
            return $this->unauthorized('Token expirado.');
        }

        if (! empty($requiredPermission) && ! $payload->hasPermission($requiredPermission)) {
            return $this->forbidden("Permiso requerido: {$requiredPermission}");
        }

        $authenticated = new AuthenticatedUser(
            userId: $payload->sub,
            email: $claims['email'] ?? '',
            firstName: $claims['first_name'] ?? '',
            lastName: $claims['last_name'] ?? '',
            activeRoleId: $payload->roleId,
            activeRoleName: $payload->roleName,
            activeBranchId: $payload->branchId,
            permissions: $payload->permissions,
            sessionId: $payload->sessionId,
            jti: $payload->jti,
            tokenExp: $payload->exp,
        );

        $request->attributes->set('authenticated_user', $authenticated);

        return $next($request);
    }

    /**
     * Estrategia de extracción del token — orden de prioridad:
     *
     * 1. Header Authorization: Bearer ... (MOBILE y Postman/Swagger)
     * 2. Cookie HttpOnly 'access_token' (WEB — el browser la adjunta automáticamente)
     *
     * Esto permite que el mismo middleware sirva a ambos tipos de cliente
     * sin configuración adicional.
     */
    private function extractToken(Request $request): ?string
    {
        // 1. Header Bearer — prioridad para clientes móviles y herramientas API
        $authHeader = $request->header('Authorization', '');
        if (str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 2. Cookie HttpOnly — clientes web Angular
        $cookieToken = $request->cookie('access_token');
        if (! empty($cookieToken)) {
            return $cookieToken;
        }

        return null;
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 403);
    }
}
