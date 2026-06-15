<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Application\UseCase\DeactivateStatus;

final readonly class DeactivateStatusCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
