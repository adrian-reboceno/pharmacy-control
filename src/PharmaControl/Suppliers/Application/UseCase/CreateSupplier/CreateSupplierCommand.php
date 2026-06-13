<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\CreateSupplier;

final readonly class CreateSupplierCommand
{
    public function __construct(
        public readonly string  $type,
        public readonly ?string $rfc,
        public readonly string  $legalName,
        public readonly ?string $tradeName,
        public readonly array   $address,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly string  $actorUserId,
    ) {}
}
