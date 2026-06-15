<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Application\UseCase\CreateIngredient;

final readonly class CreateIngredientCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $dciCode,
        public readonly ?string $casNumber,
        public readonly ?string $description,
        public readonly string $actorUserId,
    ) {}
}
