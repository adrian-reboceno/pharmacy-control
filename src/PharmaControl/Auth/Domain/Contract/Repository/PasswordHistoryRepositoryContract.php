<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Repository/PasswordHistoryRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Repository;

use PharmaControl\Auth\Domain\ValueObject\HashedPassword;
use PharmaControl\Auth\Domain\ValueObject\UserId;

interface PasswordHistoryRepositoryContract
{
    public function save(UserId $userId, HashedPassword $hash): void;

    /** @return list<HashedPassword> */
    public function findRecentByUser(UserId $userId, int $count): array;
}
