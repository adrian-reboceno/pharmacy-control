<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\DTO;

use PharmaControl\Suppliers\Domain\Model\Supplier;

final readonly class SupplierDTO
{
    public function __construct(
        public readonly string     $id,
        public readonly string     $type,
        public readonly string     $typeLabel,
        public readonly ?string    $rfc,
        public readonly string     $legalName,
        public readonly ?string    $tradeName,
        public readonly AddressDTO $address,
        public readonly ?string    $phone,
        public readonly ?string    $email,
        public readonly bool       $isActive,
        public readonly ?string    $createdBy,
        public readonly string     $createdAt,
        public readonly string     $updatedAt,
    ) {}

    public static function fromDomain(Supplier $s): self
    {
        return new self(
            id:        $s->getId()->value,
            type:      $s->getType()->value,
            typeLabel: $s->getType()->label(),
            rfc:       $s->getRfc()?->value,
            legalName: $s->getLegalName()->value,
            tradeName: $s->getTradeName(),
            address:   AddressDTO::fromDomain($s->getAddress()),
            phone:     $s->getPhone()?->value,
            email:     $s->getEmail()?->value,
            isActive:  $s->isActive(),
            createdBy: $s->getCreatedBy()?->value,
            createdAt: $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $s->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
