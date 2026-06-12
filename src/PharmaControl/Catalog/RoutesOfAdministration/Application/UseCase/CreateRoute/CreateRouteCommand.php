<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\CreateRoute;

final readonly class CreateRouteCommand
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $code,
        public readonly ?string $description,
        public readonly string  $actorUserId,
    ) {}
}
