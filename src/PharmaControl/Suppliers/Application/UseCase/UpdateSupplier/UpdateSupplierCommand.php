<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\UpdateSupplier;

final readonly class UpdateSupplierCommand
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $legalName,
        public readonly ?string $tradeName,
        public readonly array   $address,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly string  $actorUserId,
    ) {}
}
