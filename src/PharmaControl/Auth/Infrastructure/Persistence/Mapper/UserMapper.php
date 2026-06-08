<?php

// ── ARCHIVO: src/PharmaControl/Auth/Infrastructure/Persistence/Mapper/UserMapper.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\ValueObject\Email;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Auth\Domain\ValueObject\UserStatus;
use PharmaControl\Auth\Infrastructure\Persistence\Eloquent\Model\EloquentUser;

final class UserMapper
{
    public function toDomain(EloquentUser $eloquent): User
    {
        return User::reconstitute(
            id: new UserId($eloquent->id),
            email: new Email($eloquent->email),
            passwordHash: new HashedPassword($eloquent->password_hash),
            firstName: $eloquent->first_name,
            lastName: $eloquent->last_name,
            phone: $eloquent->phone,
            status: UserStatus::from($eloquent->status),
            mustChangePassword: (bool) $eloquent->must_change_password,
            twoFactorEnabled: (bool) $eloquent->two_factor_enabled,
            twoFactorSecret: $eloquent->two_factor_secret,
            failedLoginAttempts: (int) $eloquent->failed_login_attempts,
            lockedUntil: $eloquent->locked_until instanceof \DateTimeImmutable
                ? $eloquent->locked_until
                : ($eloquent->locked_until ? new \DateTimeImmutable($eloquent->locked_until) : null),
            passwordChangedAt: $eloquent->password_changed_at instanceof \DateTimeImmutable
                ? $eloquent->password_changed_at
                : ($eloquent->password_changed_at ? new \DateTimeImmutable($eloquent->password_changed_at) : null),
            lastLoginAt: $eloquent->last_login_at instanceof \DateTimeImmutable
                ? $eloquent->last_login_at
                : ($eloquent->last_login_at ? new \DateTimeImmutable($eloquent->last_login_at) : null),
            lastActivityAt: $eloquent->last_activity_at instanceof \DateTimeImmutable
                ? $eloquent->last_activity_at
                : ($eloquent->last_activity_at ? new \DateTimeImmutable($eloquent->last_activity_at) : null),
            emailVerifiedAt: $eloquent->email_verified_at instanceof \DateTimeImmutable
                ? $eloquent->email_verified_at
                : ($eloquent->email_verified_at ? new \DateTimeImmutable($eloquent->email_verified_at) : null),
        );
    }

    public function toPersistence(User $user): array
    {
        return [
            'id' => $user->id->value,
            'email' => $user->email->value,
            'password_hash' => $user->getPasswordHash()->value,
            'first_name' => $user->firstName,
            'last_name' => $user->lastName,
            'phone' => $user->phone,
            'status' => $user->getStatus()->value,
            'must_change_password' => $user->mustChangePassword(),
            'two_factor_enabled' => $user->isTwoFactorEnabled(),
            'two_factor_secret' => $user->getTwoFactorSecret(),
            'failed_login_attempts' => $user->getFailedLoginAttempts(),
            'locked_until' => $user->getLockedUntil()?->format('Y-m-d H:i:s'),
            'password_changed_at' => $user->getPasswordChangedAt()?->format('Y-m-d H:i:s'),
            'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
            'last_activity_at' => $user->getLastActivityAt()?->format('Y-m-d H:i:s'),
            'email_verified_at' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
