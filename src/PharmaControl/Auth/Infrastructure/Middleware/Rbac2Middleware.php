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
    private const INACTIVITY_THRESHOLD = 3600; // 1 hour in seconds

    public function __construct(
        private readonly TokenServiceContract $tokenService,
    ) {}

    public function handle(Request $request, Closure $next, string $requiredPermission = ''): mixed
    {
        $authHeader = $request->header('Authorization', '');
        if (! str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Token de autorización requerido.');
        }

        $rawToken = substr($authHeader, 7);

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

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 403);
    }
}
