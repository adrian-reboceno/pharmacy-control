<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Policy/PasswordPolicy.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Policy;

use PharmaControl\Auth\Domain\Model\Role;
use PharmaControl\Auth\Domain\Model\User;
use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\Password;

final class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    public const REQUIRE_UPPERCASE = true;

    public const REQUIRE_LOWERCASE = true;

    public const REQUIRE_NUMBER = true;

    public const REQUIRE_SPECIAL = true;

    public const HISTORY_COUNT = 5;

    public const ROTATION_DAYS_HIGH = 90;

    public const ROTATION_DAYS_LOW = 180;

    public const HIGH_HIERARCHY = 8;

    public function validate(Password $password): void
    {
        // Validation is already enforced in the Password VO constructor.
        // This method exists as an explicit policy gate in use cases.
    }

    public function isExpired(User $user, Role $role): bool
    {
        $days = $role->hierarchyLevel >= self::HIGH_HIERARCHY
            ? self::ROTATION_DAYS_HIGH
            : self::ROTATION_DAYS_LOW;

        return $user->isPasswordExpired($days);
    }

    /** @param list<HashedPassword> $history */
    public function wasRecentlyUsed(HashedPassword $newHash, array $history): bool
    {
        foreach ($history as $old) {
            if (password_verify($newHash->value, $old->value) || $newHash->value === $old->value) {
                return true;
            }
        }

        return false;
    }

    public function makeHash(string $plainPassword): HashedPassword
    {
        return new HashedPassword(
            password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12])
        );
    }
}
