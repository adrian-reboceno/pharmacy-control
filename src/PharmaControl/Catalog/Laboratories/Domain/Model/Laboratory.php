<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/Model/Laboratory.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryCreated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryDeactivated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryUpdated;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\CountryCode;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryName;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\WebsiteUrl;
use PharmaControl\Shared\Event\DomainEvent;

final class Laboratory
{
    private LaboratoryName $name;

    private CountryCode $countryCode;

    private ?WebsiteUrl $website;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly LaboratoryId $id,
        LaboratoryName $name,
        CountryCode $countryCode,
        ?WebsiteUrl $website,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->name = $name;
        $this->countryCode = $countryCode;
        $this->website = $website;
        $this->isActive = $isActive;
    }

    public static function create(
        LaboratoryId $id,
        LaboratoryName $name,
        CountryCode $countryCode,
        ?WebsiteUrl $website,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $lab = new self($id, $name, $countryCode, $website, true, $createdBy, $now, $now);
        $lab->recordEvent(new LaboratoryCreated(
            id: $id,
            name: $name,
            countryCode: $countryCode,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $lab;
    }

    public static function reconstitute(
        LaboratoryId $id,
        LaboratoryName $name,
        CountryCode $countryCode,
        ?WebsiteUrl $website,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $countryCode, $website, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(LaboratoryName $name, CountryCode $countryCode, ?WebsiteUrl $website): void
    {
        $previousName = $this->name;
        $previousCountryCode = $this->countryCode;
        $previousWebsite = $this->website;

        $this->name = $name;
        $this->countryCode = $countryCode;
        $this->website = $website;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new LaboratoryUpdated(
            id: $this->id,
            previousName: $previousName,
            newName: $name,
            previousCountryCode: $previousCountryCode,
            newCountryCode: $countryCode,
            previousWebsite: $previousWebsite,
            newWebsite: $website,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('El laboratorio ya está inactivo.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new LaboratoryDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): LaboratoryId
    {
        return $this->id;
    }

    public function getName(): LaboratoryName
    {
        return $this->name;
    }

    public function getCountryCode(): CountryCode
    {
        return $this->countryCode;
    }

    public function getWebsite(): ?WebsiteUrl
    {
        return $this->website;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedBy(): ?UserId
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
