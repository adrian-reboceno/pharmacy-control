<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\UpdateIngredient;

final readonly class UpdateIngredientCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $dciCode,
        public readonly ?string $casNumber,
        public readonly ?string $description,
        public readonly string $actorUserId,
    ) {}
}
