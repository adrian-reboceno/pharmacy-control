<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\DeactivateProduct;

final readonly class DeactivateProductCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
