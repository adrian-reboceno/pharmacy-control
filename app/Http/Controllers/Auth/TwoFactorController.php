<?php

// ── ARCHIVO: app/Http/Controllers/Auth/TwoFactorController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\TwoFactorController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/2fa/setup', middleware: ['auth:sanctum'])]
    public function setup(Request $request): JsonResponse
    {
        /** @var AuthenticatedUser $user */
        $user = $request->attributes->get('authenticated_user');
        $result = $this->controller->setup(['user_id' => $user->userId]);

        return response()->json(['data' => [
            'qr_uri' => $result['qr_uri'],
            'secret' => $result['secret'],
        ]], 200);
    }

    #[Route('POST', '/api/v1/auth/2fa/verify', middleware: ['throttle:3,1'])]
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'uuid'],
            'totp_code' => ['required', 'string', 'size:6'],
            'secret' => ['required', 'string'],
        ]);

        $result = $this->controller->verify([
            'user_id' => $request->string('user_id')->toString(),
            'totp_code' => $request->string('totp_code')->toString(),
            'secret' => $request->string('secret')->toString(),
        ]);

        return response()->json(['data' => $result], 200);
    }
}
