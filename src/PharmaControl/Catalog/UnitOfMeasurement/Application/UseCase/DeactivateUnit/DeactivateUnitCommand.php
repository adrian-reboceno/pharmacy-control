<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\DeactivateUnit;

final readonly class DeactivateUnitCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
