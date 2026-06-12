<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\UpdatePresentation;

final readonly class UpdatePresentationCommand
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $abbreviation,
        public readonly ?string $description,
        public readonly string  $actorUserId,
    ) {}
}
