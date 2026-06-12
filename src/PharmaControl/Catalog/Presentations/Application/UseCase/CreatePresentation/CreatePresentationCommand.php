<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\CreatePresentation;

final readonly class CreatePresentationCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $abbreviation,
        public readonly ?string $description,
        public readonly string $actorUserId,
    ) {}
}
