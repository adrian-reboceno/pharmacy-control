<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Domain\Event;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Shared\Event\DomainEvent;
use PharmaControl\Suppliers\Domain\ValueObject\LegalName;
use PharmaControl\Suppliers\Domain\ValueObject\Rfc;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierId;
use PharmaControl\Suppliers\Domain\ValueObject\SupplierType;

final readonly class SupplierCreated implements DomainEvent
{
    public function __construct(
        public readonly SupplierId         $id,
        public readonly SupplierType       $type,
        public readonly ?Rfc               $rfc,
        public readonly LegalName          $legalName,
        public readonly ?UserId            $createdBy,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
