<?php

// ── ARCHIVO: app/Http/Controllers/Auth/UserController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\UserController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class UserController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/users', middleware: ['auth:sanctum', 'rbac2:auth.users.create'])]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:15'],
        ]);

        /** @var AuthenticatedUser $actor */
        $actor = $request->attributes->get('authenticated_user');

        $result = $this->controller->create([
            'email' => $request->string('email')->toString(),
            'first_name' => $request->string('first_name')->toString(),
            'last_name' => $request->string('last_name')->toString(),
            'phone' => $request->input('phone'),
            'actor_user_id' => $actor->userId,
        ]);

        return (new UserResource($result))
            ->response()
            ->setStatusCode(201);
    }

    #[Route('POST', '/api/v1/users/{id}/unlock', middleware: ['auth:sanctum', 'rbac2:auth.users.unlock'])]
    #[OA\Post(
        path: '/v1/auth/users/{userId}/unlock',
        summary: 'Desbloquear cuenta',
        description: 'Desbloquea una cuenta bloqueada por intentos fallidos. Requiere privilegios de administrador.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, description: 'La cuenta no está bloqueada'),
        ]
    )]
    public function unlock(Request $request, string $id): JsonResponse
    {
        /** @var AuthenticatedUser $actor */
        $actor = $request->attributes->get('authenticated_user');

        $this->controller->unlock([
            'target_user_id' => $id,
            'actor_user_id' => $actor->userId,
        ]);

        return response()->json(['message' => 'Cuenta desbloqueada exitosamente.'], 200);
    }

    #[Route('GET', '/api/v1/auth/me', middleware: ['auth:sanctum'])]
    public function me(Request $request): JsonResponse
    {
        /** @var AuthenticatedUser $user */
        $user = $request->attributes->get('authenticated_user');

        return response()->json(['data' => [
            'user_id' => $user->userId,
            'email' => $user->email,
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
            'active_role_id' => $user->activeRoleId,
            'active_role' => $user->activeRoleName,
            'active_branch_id' => $user->activeBranchId,
            'permissions' => $user->permissions,
            'session_id' => $user->sessionId,
        ]], 200);
    }
}
