<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteCreated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteDeactivated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteUpdated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PharmaControl\Shared\Event\DomainEvent;

final class RouteOfAdministration
{
    private RouteName $name;

    private RouteCode $code;

    private ?string $description;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly RouteId $id,
        RouteName $name,
        RouteCode $code,
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
        RouteId $id,
        RouteName $name,
        RouteCode $code,
        ?string $description,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $route = new self($id, $name, $code, $description, true, $createdBy, $now, $now);
        $route->recordEvent(new RouteCreated(
            id: $id,
            name: $name,
            code: $code,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $route;
    }

    public static function reconstitute(
        RouteId $id,
        RouteName $name,
        RouteCode $code,
        ?string $description,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $name, $code, $description, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(
        RouteName $name,
        RouteCode $code,
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

        $this->recordEvent(new RouteUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('La vía de administración ya está inactiva.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new RouteDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): RouteId
    {
        return $this->id;
    }

    public function getName(): RouteName
    {
        return $this->name;
    }

    public function getCode(): RouteCode
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
