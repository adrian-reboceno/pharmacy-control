<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Domain/Model/MedicationClassification.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationCreated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationDeactivated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationUpdated;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationName;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\PrescriptionType;
use PharmaControl\Shared\Event\DomainEvent;

final class MedicationClassification
{
    private ClassificationName $name;

    private PrescriptionType $prescriptionType;

    private ?int $validityDays;

    private ?string $validityNote;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly ClassificationId $id,
        public readonly LgsGroup $lgsGroup,
        ClassificationName $name,
        PrescriptionType $prescriptionType,
        ?int $validityDays,
        ?string $validityNote,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->name = $name;
        $this->prescriptionType = $prescriptionType;
        $this->validityDays = $validityDays;
        $this->validityNote = $validityNote;
        $this->isActive = $isActive;
    }

    public static function create(
        ClassificationId $id,
        LgsGroup $lgsGroup,
        ClassificationName $name,
        PrescriptionType $prescriptionType,
        ?int $validityDays,
        ?string $validityNote,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $classification = new self(
            $id, $lgsGroup, $name, $prescriptionType,
            $validityDays, $validityNote, true, $createdBy, $now, $now,
        );
        $classification->recordEvent(new ClassificationCreated(
            id: $id,
            lgsGroup: $lgsGroup,
            name: $name,
            occurredAt: $now,
        ));

        return $classification;
    }

    public static function reconstitute(
        ClassificationId $id,
        LgsGroup $lgsGroup,
        ClassificationName $name,
        PrescriptionType $prescriptionType,
        ?int $validityDays,
        ?string $validityNote,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id, $lgsGroup, $name, $prescriptionType,
            $validityDays, $validityNote, $isActive, $createdBy, $createdAt, $updatedAt,
        );
    }

    public function update(
        ClassificationName $name,
        PrescriptionType $prescriptionType,
        ?int $validityDays,
        ?string $validityNote,
    ): void {
        $changes = [];

        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if ($this->prescriptionType !== $prescriptionType) {
            $changes['prescription_type'] = ['old' => $this->prescriptionType->value, 'new' => $prescriptionType->value];
        }
        if ($this->validityDays !== $validityDays) {
            $changes['validity_days'] = ['old' => $this->validityDays, 'new' => $validityDays];
        }
        if ($this->validityNote !== $validityNote) {
            $changes['validity_note'] = ['old' => $this->validityNote, 'new' => $validityNote];
        }

        $this->name = $name;
        $this->prescriptionType = $prescriptionType;
        $this->validityDays = $validityDays;
        $this->validityNote = $validityNote;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new ClassificationUpdated(
            id: $this->id,
            lgsGroup: $this->lgsGroup,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('La clasificación ya está inactiva.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new ClassificationDeactivated(
            id: $this->id,
            lgsGroup: $this->lgsGroup,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isControlled(): bool
    {
        return in_array($this->lgsGroup, [
            LgsGroup::I, LgsGroup::II, LgsGroup::III,
            LgsGroup::IV_A, LgsGroup::IV_B,
        ], true);
    }

    public function getId(): ClassificationId
    {
        return $this->id;
    }

    public function getLgsGroup(): LgsGroup
    {
        return $this->lgsGroup;
    }

    public function getName(): ClassificationName
    {
        return $this->name;
    }

    public function getPrescriptionType(): PrescriptionType
    {
        return $this->prescriptionType;
    }

    public function getValidityDays(): ?int
    {
        return $this->validityDays;
    }

    public function getValidityNote(): ?string
    {
        return $this->validityNote;
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
