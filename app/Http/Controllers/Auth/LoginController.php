<?php

// ── ARCHIVO: app/Http/Controllers/Auth/LoginController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\AuthTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\LoginController as AuthController;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/login', middleware: ['throttle:5,1'])]
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
