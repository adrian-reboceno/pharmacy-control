<?php

// ── ARCHIVO: app/Http/Middleware/Rbac2Authorize.php ──
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PharmaControl\Auth\Infrastructure\Middleware\Rbac2Middleware;

final class Rbac2Authorize
{
    public function __construct(
        private readonly Rbac2Middleware $middleware,
    ) {}

    public function handle(Request $request, Closure $next, string $requiredPermission = ''): mixed
    {
        return $this->middleware->handle($request, $next, $requiredPermission);
    }
}
