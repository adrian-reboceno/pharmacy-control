<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\DTO;

use PharmaControl\Suppliers\Domain\ValueObject\Address;

final readonly class AddressDTO
{
    public function __construct(
        public readonly string  $street,
        public readonly string  $extNumber,
        public readonly ?string $intNumber,
        public readonly string  $neighborhood,
        public readonly string  $municipality,
        public readonly string  $state,
        public readonly string  $postalCode,
        public readonly string  $country,
        public readonly string  $fullAddress,
    ) {}

    public static function fromDomain(Address $address): self
    {
        return new self(
            street:       $address->street,
            extNumber:    $address->extNumber,
            intNumber:    $address->intNumber,
            neighborhood: $address->neighborhood,
            municipality: $address->municipality,
            state:        $address->state,
            postalCode:   $address->postalCode,
            country:      $address->country,
            fullAddress:  $address->toFullString(),
        );
    }
}
