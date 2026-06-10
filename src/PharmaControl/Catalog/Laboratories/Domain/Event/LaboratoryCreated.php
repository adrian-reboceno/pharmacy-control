<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/Event/LaboratoryCreated.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class LaboratoryCreated implements DomainEvent
{
    public function __construct(
        public readonly LaboratoryId $id,
        public readonly LaboratoryName $name,
        public readonly CountryCode $countryCode,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
