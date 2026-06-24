<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\DeactivateLocation;

final readonly class DeactivateLocationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
