<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use OpenApi\Attributes as OA;
use PharmaControl\Auth\Infrastructure\Controller\RefreshTokenController as AuthController;

class RefreshTokenController extends Controller
{
    private const ACCESS_TTL  = 900;
    private const REFRESH_TTL = 604_800;

    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/refresh', middleware: ['throttle:10,1'])]
    #[OA\Post(
        path: '/v1/auth/refresh',
        summary: 'Renovar tokens',
        description: 'WEB: lee refresh_token de cookie HttpOnly y rota ambas cookies. MOBILE: acepta refresh_token en body y devuelve tokens en body.',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string', description: 'Solo para MOBILE'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tokens renovados'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        // WEB: leer de la cookie HttpOnly (adjuntada automáticamente por el browser)
        // MOBILE: leer del body JSON
        $refreshToken = $request->cookie('refresh_token')
            ?? $request->string('refresh_token')->toString();

        if (empty($refreshToken)) {
            return response()->json(['message' => 'Refresh token requerido.'], 401);
        }

        /** @var \PharmaControl\Auth\Application\DTO\AuthTokenDTO $result */
        $result = ($this->controller)([
            'refresh_token' => $refreshToken,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ]);

        // MOBILE: devolver tokens en el body JSON
        if (!$request->hasCookie('refresh_token') && $request->has('refresh_token')) {
            return response()->json([
                'data' => [
                    'access_token'  => $result->accessToken,
                    'refresh_token' => $result->refreshToken,
                    'expires_in'    => $result->expiresIn,
                ],
            ], 200);
        }

        // WEB: rotar ambas cookies HttpOnly
        $secure   = config('app.env') === 'production';
        $sameSite = $secure ? 'Strict' : 'Lax';

        $accessCookie = cookie(
            name:     'access_token',
            value:    $result->accessToken,
            minutes:  self::ACCESS_TTL / 60,
            path:     '/',
            domain:   null,
            secure:   $secure,
            httpOnly: true,
            raw:      false,
            sameSite: $sameSite,
        );

        $refreshCookie = cookie(
            name:     'refresh_token',
            value:    $result->refreshToken,
            minutes:  self::REFRESH_TTL / 60,
            path:     '/api/v1/auth/refresh',
            domain:   null,
            secure:   $secure,
            httpOnly: true,
            raw:      false,
            sameSite: $sameSite,
        );

        return response()
            ->json([
                'data' => [
                    'expires_in' => $result->expiresIn,
                ],
            ], 200)
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }
}