<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;
use PharmaControl\Suppliers\Domain\Event\SupplierCreated;
use PharmaControl\Suppliers\Domain\Event\SupplierDeactivated;
use PharmaControl\Suppliers\Domain\Event\SupplierUpdated;
use PharmaControl\Suppliers\Domain\ValueObject\Address;
use PharmaControl\Suppliers\Domain\ValueObject\Email;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Phone;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;

final class Supplier
{
    private ?Rfc $rfc;

    private LegalName $legalName;

    private ?string $tradeName;

    private Address $address;

    private ?Phone $phone;

    private ?Email $email;

    private bool $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly SupplierId $id,
        public readonly SupplierType $type,
        ?Rfc $rfc,
        LegalName $legalName,
        ?string $tradeName,
        Address $address,
        ?Phone $phone,
        ?Email $email,
        bool $isActive,
        public readonly ?UserId $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $this->rfc = $rfc;
        $this->legalName = $legalName;
        $this->tradeName = $tradeName;
        $this->address = $address;
        $this->phone = $phone;
        $this->email = $email;
        $this->isActive = $isActive;
    }

    public static function create(
        SupplierId $id,
        SupplierType $type,
        ?Rfc $rfc,
        LegalName $legalName,
        ?string $tradeName,
        Address $address,
        ?Phone $phone,
        ?Email $email,
        ?UserId $createdBy,
    ): self {
        $now = new \DateTimeImmutable;
        $supplier = new self($id, $type, $rfc, $legalName, $tradeName, $address, $phone, $email, true, $createdBy, $now, $now);
        $supplier->recordEvent(new SupplierCreated(
            id: $id,
            type: $type,
            rfc: $rfc,
            legalName: $legalName,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $supplier;
    }

    public static function reconstitute(
        SupplierId $id,
        SupplierType $type,
        ?Rfc $rfc,
        LegalName $legalName,
        ?string $tradeName,
        Address $address,
        ?Phone $phone,
        ?Email $email,
        bool $isActive,
        ?UserId $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $type, $rfc, $legalName, $tradeName, $address, $phone, $email, $isActive, $createdBy, $createdAt, $updatedAt);
    }

    public function update(
        LegalName $legalName,
        ?string $tradeName,
        Address $address,
        ?Phone $phone,
        ?Email $email,
    ): void {
        $changes = [];

        if (! $this->legalName->equals($legalName)) {
            $changes['legal_name'] = ['old' => $this->legalName->value, 'new' => $legalName->value];
        }
        if ($this->tradeName !== $tradeName) {
            $changes['trade_name'] = ['old' => $this->tradeName, 'new' => $tradeName];
        }
        if (! $this->address->equals($address)) {
            $changes['address'] = ['old' => $this->address->toFullString(), 'new' => $address->toFullString()];
        }
        if (($this->phone?->value) !== ($phone?->value)) {
            $changes['phone'] = ['old' => $this->phone?->value, 'new' => $phone?->value];
        }
        if (($this->email?->value) !== ($email?->value)) {
            $changes['email'] = ['old' => $this->email?->value, 'new' => $email?->value];
        }

        $this->legalName = $legalName;
        $this->tradeName = $tradeName;
        $this->address = $address;
        $this->phone = $phone;
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable;

        $this->recordEvent(new SupplierUpdated(
            id: $this->id,
            changes: $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('El proveedor ya está inactivo.');
        }
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new SupplierDeactivated(
            id: $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getId(): SupplierId
    {
        return $this->id;
    }

    public function getType(): SupplierType
    {
        return $this->type;
    }

    public function getRfc(): ?Rfc
    {
        return $this->rfc;
    }

    public function getLegalName(): LegalName
    {
        return $this->legalName;
    }

    public function getTradeName(): ?string
    {
        return $this->tradeName;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
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
