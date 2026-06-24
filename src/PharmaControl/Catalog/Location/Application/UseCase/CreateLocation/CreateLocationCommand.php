<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\CreateLocation;

final readonly class CreateLocationCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int $level,
        public readonly ?string $parentId,
        public readonly ?string $description,
        public readonly string $actorUserId,
    ) {}
}
