<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\UpdateLocation;

final readonly class UpdateLocationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $actorUserId,
    ) {}
}
