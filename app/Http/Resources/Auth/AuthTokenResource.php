<?php

// ── ARCHIVO: app/Http/Resources/Auth/AuthTokenResource.php ──
declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Auth\Application\DTO\AuthTokenDTO;

class AuthTokenResource extends JsonResource
{
    public function __construct(AuthTokenDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var AuthTokenDTO $dto */
        $dto = $this->resource;

        return [
            'token_type' => $dto->tokenType,
            'access_token' => $dto->accessToken,
            'refresh_token' => $dto->refreshToken,
            'expires_in' => $dto->expiresIn,
            'requires_password_change' => $dto->requiresPasswordChange,
            'requires_role_selection' => $dto->requiresRoleSelection,
            'available_roles' => $dto->availableRoles,
            'active_role_id' => $dto->activeRoleId,
            'active_branch_id' => $dto->activeBranchId,
        ];
    }
}
