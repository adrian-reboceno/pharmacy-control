<?php

// ── ARCHIVO: app/Http/Controllers/Auth/RoleController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use OpenApi\Attributes as OA;
use PharmaControl\Auth\Infrastructure\Controller\RoleController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class RoleController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/users/{id}/roles', middleware: ['auth:sanctum', 'rbac2:auth.roles.assign'])]
    #[OA\Post(
        path: '/v1/auth/users/{userId}/roles',
        summary: 'Asignar rol a usuario',
        description: 'El actor solo puede asignar roles de nivel ≤ al suyo. Máximo 3 roles. Respeta SSoD.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role_id'],
                properties: [
                    new OA\Property(property: 'role_id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'branch_id', type: 'string', format: 'uuid', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, ref: '#/components/responses/NoContent'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 403, ref: '#/components/responses/Forbidden'),
            new OA\Response(response: 409, description: 'Rol ya asignado / violación SSoD / super-admin exclusivo'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function assign(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'role_id' => ['required', 'uuid'],
            'branch_id' => ['sometimes', 'nullable', 'uuid'],
        ]);

        /** @var AuthenticatedUser $actor */
        $actor = $request->attributes->get('authenticated_user');

        $this->controller->assign([
            'target_user_id' => $id,
            'role_id' => $request->string('role_id')->toString(),
            'branch_id' => $request->input('branch_id'),
            'actor_user_id' => $actor->userId,
            'actor_role_id' => $actor->activeRoleId,
        ]);

        return response()->json(['message' => 'Rol asignado exitosamente.'], 201);
    }

    #[Route('DELETE', '/api/v1/users/{id}/roles/{roleId}', middleware: ['auth:sanctum', 'rbac2:auth.roles.revoke'])]
    public function revoke(Request $request, string $id, string $roleId): JsonResponse
    {
        /** @var AuthenticatedUser $actor */
        $actor = $request->attributes->get('authenticated_user');

        $this->controller->revoke([
            'target_user_id' => $id,
            'role_id' => $roleId,
            'actor_user_id' => $actor->userId,
        ]);

        return response()->json(['message' => 'Rol revocado exitosamente.'], 200);
    }
}
