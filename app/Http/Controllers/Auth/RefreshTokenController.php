<?php

// ── ARCHIVO: app/Http/Controllers/Auth/RefreshTokenController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\RefreshTokenController as AuthController;

class RefreshTokenController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/refresh', middleware: ['throttle:10,1'])]
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
