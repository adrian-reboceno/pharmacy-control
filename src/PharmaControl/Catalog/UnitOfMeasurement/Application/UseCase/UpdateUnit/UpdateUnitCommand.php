<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\UpdateUnit;

final readonly class UpdateUnitCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $symbol,
        public readonly string $type,
        public readonly string $actorUserId,
    ) {}
}
