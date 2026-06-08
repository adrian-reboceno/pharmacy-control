<?php

// ── ARCHIVO: src/PharmaControl/Shared/ValueObject/AuditInfo.php ──
declare(strict_types=1);

namespace PharmaControl\Shared\ValueObject;

final readonly class AuditInfo
{
    public function __construct(
        public readonly Uuid $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?Uuid $updatedBy,
        public readonly ?\DateTimeImmutable $updatedAt,
    ) {}
}
