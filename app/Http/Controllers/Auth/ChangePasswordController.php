<?php

// ── ARCHIVO: app/Http/Controllers/Auth/ChangePasswordController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\ChangePasswordController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('POST', '/api/v1/auth/password/change', middleware: ['auth:sanctum'])]
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        /** @var AuthenticatedUser $user */
        $user = $request->attributes->get('authenticated_user');

        ($this->controller)([
            'user_id' => $user->userId,
            'current_password' => $request->string('current_password')->toString(),
            'new_password' => $request->string('new_password')->toString(),
            'session_id' => $user->sessionId,
        ]);

        return response()->json(['message' => 'Contraseña actualizada exitosamente.'], 200);
    }
}
