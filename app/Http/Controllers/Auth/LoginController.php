<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use OpenApi\Attributes as OA;
use PharmaControl\Auth\Application\DTO\AuthTokenDTO;
use PharmaControl\Auth\Infrastructure\Controller\LoginController as AuthController;

class LoginController extends Controller
{
    private const ACCESS_TTL = 900;

    private const REFRESH_TTL = 604_800;

    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/login', middleware: ['throttle:5,1'])]
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Iniciar sesión',
        description: 'WEB: tokens en cookies HttpOnly. MOBILE: tokens en body JSON.',
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
            new OA\Response(response: 200, description: 'Login exitoso'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
            new OA\Response(response: 423, description: 'Cuenta bloqueada'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'client_type' => ['sometimes', 'string', 'in:WEB,MOBILE'],
        ]);

        $clientType = strtoupper($request->string('client_type', 'WEB')->toString());

        /** @var AuthTokenDTO $result */
        $result = ($this->controller)([
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'client_type' => $clientType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // MOBILE — tokens en el body JSON (comportamiento original)
        if ($clientType === 'MOBILE') {
            return response()->json([
                'data' => [
                    'access_token' => $result->accessToken,
                    'refresh_token' => $result->refreshToken,
                    'expires_in' => $result->expiresIn,
                    'requires_role_selection' => $result->requiresRoleSelection,
                    'requires_password_change' => $result->requiresPasswordChange,
                    'available_roles' => $result->availableRoles,
                    'active_role_id' => $result->activeRoleId,
                    'active_branch_id' => $result->activeBranchId,
                ],
            ], 200);
        }

        // WEB — tokens en cookies HttpOnly, JavaScript nunca los ve
        $secure = config('app.env') === 'production';
        $sameSite = $secure ? 'Strict' : 'Lax';

        $accessCookie = cookie(
            name: 'access_token',
            value: $result->accessToken,
            minutes: self::ACCESS_TTL / 60,
            path: '/',
            domain: null,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: $sameSite,
        );

        $refreshCookie = cookie(
            name: 'refresh_token',
            value: $result->refreshToken,
            minutes: self::REFRESH_TTL / 60,
            path: '/api/v1/auth/refresh',
            domain: null,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: $sameSite,
        );

        return response()
            ->json([
                'data' => [
                    'expires_in' => $result->expiresIn,
                    'requires_role_selection' => $result->requiresRoleSelection,
                    'requires_password_change' => $result->requiresPasswordChange,
                    'available_roles' => $result->availableRoles,
                    'active_role_id' => $result->activeRoleId,
                    'active_branch_id' => $result->activeBranchId,
                ],
            ], 200)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }
}
