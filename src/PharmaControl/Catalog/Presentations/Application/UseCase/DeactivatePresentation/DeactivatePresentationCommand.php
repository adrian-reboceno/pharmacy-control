<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\DeactivatePresentation;

final readonly class DeactivatePresentationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
