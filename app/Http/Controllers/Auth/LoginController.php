<?php

// ── ARCHIVO: app/Http/Controllers/Auth/LoginController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use OpenApi\Attributes as OA;
use PharmaControl\Auth\Infrastructure\Controller\LoginController as AuthController;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/login', middleware: ['throttle:5,1'])]
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Iniciar sesión',
        description: 'Autentica al usuario y devuelve par de tokens JWT. Si el usuario tiene múltiples roles, requires_role_selection será true.',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'client_type'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@pharmaco.mx'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Ch4ng3M3_N0w!#2026'),
                    new OA\Property(property: 'client_type', type: 'string', enum: ['WEB', 'MOBILE'], example: 'WEB'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'access_token', type: 'string'),
                    new OA\Property(property: 'refresh_token', type: 'string'),
                    new OA\Property(property: 'expires_in', type: 'integer', example: 900),
                    new OA\Property(property: 'requires_role_selection', type: 'boolean', example: false),
                    new OA\Property(property: 'requires_password_change', type: 'boolean', example: false),
                ])
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
            new OA\Response(
                response: 423,
                description: 'Cuenta bloqueada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cuenta bloqueada.'),
                        new OA\Property(property: 'error', type: 'string', example: 'ACCOUNT_LOCKED'),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'client_type' => ['sometimes', 'string', 'in:WEB,MOBILE'],
        ]);

        $result = ($this->controller)([
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'client_type' => strtoupper($request->string('client_type', 'WEB')->toString()),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new AuthTokenResource($result))
            ->response()
            ->setStatusCode(200);
    }
}
