<?php

// ── ARCHIVO: app/Http/Controllers/Auth/AuditLogController.php ──
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Route;
use PharmaControl\Auth\Infrastructure\Controller\AuditLogController as AuthController;
use PharmaControl\Auth\Infrastructure\Middleware\AuthenticatedUser;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuthController $controller,
    ) {}

    #[Route('GET', '/api/v1/audit', middleware: ['auth:sanctum', 'rbac2:auth.audit.view'])]
    public function index(Request $request): JsonResponse
    {
        /** @var AuthenticatedUser $user */
        $user = $request->attributes->get('authenticated_user');

        $entries = $this->controller->index([
            'user_id' => $user->userId,
            'limit' => (int) $request->query('limit', 50),
            'offset' => (int) $request->query('offset', 0),
        ]);

        return response()->json(['data' => $entries], 200);
    }
}
