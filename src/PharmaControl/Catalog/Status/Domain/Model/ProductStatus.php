<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Status\Domain\Event\StatusCreated;
use PharmaControl\Catalog\Status\Domain\Event\StatusDeactivated;
use PharmaControl\Catalog\Status\Domain\Event\StatusUpdated;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PharmaControl\Shared\Event\DomainEvent;

final class ProductStatus
{
    private StatusName $name;

    private StatusCode $code;

    private ?string $description;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly StatusId $id,
        StatusName $name,
        StatusCode $code,
        ?string $description,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->isActive = $isActive;
    }

    public static function create(
        StatusId $id,
        StatusName $name,
        StatusCode $code,
        ?string $description,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $status = new self($id, $name, $code, $description, true, $createdBy, $now, $now);
        $status->recordEvent(new StatusCreated(
            id: $id,
            name: $name,
            code: $code,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $status;
    }

    public static function reconstitute(
        StatusId $id,
        StatusName $name,
        StatusCode $code,
        ?string $description,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $code, $description, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(
        StatusName $name,
        StatusCode $code,
        ?string $description,
    ): void {
        $changes = [];

        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if (! $this->code->equals($code)) {
            $changes['code'] = ['old' => $this->code->value, 'new' => $code->value];
        }
        if ($this->description !== $description) {
            $changes['description'] = ['old' => $this->description, 'new' => $description];
        }

        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new StatusUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('El estado de producto ya está inactivo.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new StatusDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): StatusId
    {
        return $this->id;
    }

    public function getName(): StatusName
    {
        return $this->name;
    }

    public function getCode(): StatusCode
    {
        return $this->code;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
