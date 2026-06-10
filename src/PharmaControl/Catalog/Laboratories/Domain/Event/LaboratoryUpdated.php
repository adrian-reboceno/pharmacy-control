<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/Event/LaboratoryUpdated.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\Event;

use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\WebsiteUrl;
use PharmaControl\Shared\Event\DomainEvent;

final readonly class LaboratoryUpdated implements DomainEvent
{
    public function __construct(
        public readonly LaboratoryId $id,
        public readonly LaboratoryName $previousName,
        public readonly LaboratoryName $newName,
        public readonly CountryCode $previousCountryCode,
        public readonly CountryCode $newCountryCode,
        public readonly ?WebsiteUrl $previousWebsite,
        public readonly ?WebsiteUrl $newWebsite,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
