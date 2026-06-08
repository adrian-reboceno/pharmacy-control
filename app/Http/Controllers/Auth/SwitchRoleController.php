<?php

// ── ARCHIVO: app/Http/Controllers/Auth/SwitchRoleController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\SwitchRoleController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class SwitchRoleController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/session/role', middleware: ['auth:sanctum'])]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => ['required', 'uuid'],
            'branch_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        /** @var AuthenticatedUser $user */
        $user = $request->attributes->get('authenticated_user');

        $result = ($this->controller)([
            'user_id' => $user->userId,
            'session_id' => $user->sessionId,
            'target_role_id' => $request->string('role_id')->toString(),
            'branch_id' => $request->input('branch_id'),
            'current_jti' => $user->jti,
            'current_token_ttl' => $user->tokenTtlRemaining(),
        ]);

        return (new AuthTokenResource($result))
            ->response()
            ->setStatusCode(200);
    }
}
