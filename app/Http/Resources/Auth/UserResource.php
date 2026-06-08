<?php

// ── ARCHIVO: app/Http/Resources/Auth/UserResource.php ──
declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Auth\Application\DTO\UserDTO;

class UserResource extends JsonResource
{
    public function __construct(UserDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var UserDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'email' => $dto->email,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'phone' => $dto->phone,
            'status' => $dto->status,
            'two_factor_enabled' => $dto->twoFactorEnabled,
            'must_change_password' => $dto->mustChangePassword,
            'last_login_at' => $dto->lastLoginAt,
            'email_verified_at' => $dto->emailVerifiedAt,
        ];
    }
}
