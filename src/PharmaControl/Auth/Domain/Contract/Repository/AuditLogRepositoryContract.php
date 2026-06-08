<?php

// ── ARCHIVO: src/PharmaControl/Auth/Domain/Contract/Repository/AuditLogRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Auth\Domain\Contract\Repository;

use PharmaControl\Auth\Domain\Model\AuditEntry;
use PharmaControl\Auth\Domain\ValueObject\UserId;

interface AuditLogRepositoryContract
{
    public function append(AuditEntry $entry): void;

    /** @return list<AuditEntry> */
    public function findByUser(UserId $userId, int $limit, int $offset): array;

    /** @return list<AuditEntry> */
    public function findByModule(string $module, \DateTimeImmutable $from, \DateTimeImmutable $to): array;
}
