<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\DeactivateSupplier;

final readonly class DeactivateSupplierCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
