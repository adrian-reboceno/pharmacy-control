<?php

// ── ARCHIVO: src/PharmaControl/Auth/Application/DTO/UserDTO.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Application\DTO;

final readonly class UserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phone,
        public readonly string $status,
        public readonly bool $twoFactorEnabled,
        public readonly bool $mustChangePassword,
        public readonly ?string $lastLoginAt,
        public readonly ?string $emailVerifiedAt,
    ) {}
}
