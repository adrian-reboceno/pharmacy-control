<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Application\UseCase\CreateStatus;

final readonly class CreateStatusCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly string $actorUserId,
    ) {}
}
