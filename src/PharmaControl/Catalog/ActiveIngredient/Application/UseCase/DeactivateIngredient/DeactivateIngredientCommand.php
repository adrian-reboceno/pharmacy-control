<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\DeactivateIngredient;

final readonly class DeactivateIngredientCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
