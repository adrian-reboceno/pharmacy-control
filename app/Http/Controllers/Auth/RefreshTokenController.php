<?php

// ── ARCHIVO: app/Http/Controllers/Auth/RefreshTokenController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use OpenApi\Attributes as OA;
use PharmaControl\Auth\Infrastructure\Controller\RefreshTokenController as AuthController;

class RefreshTokenController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/refresh', middleware: ['throttle:10,1'])]
    #[OA\Post(
        path: '/v1/auth/refresh',
        summary: 'Renovar tokens',
        description: 'Intercambia el refresh token por un nuevo par. El refresh token anterior queda invalidado.',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string', example: 'a3f8c2...'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tokens renovados'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthorized'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $result = ($this->controller)([
            'refresh_token' => $request->string('refresh_token')->toString(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return (new AuthTokenResource($result))
            ->response()
            ->setStatusCode(200);
    }
}
