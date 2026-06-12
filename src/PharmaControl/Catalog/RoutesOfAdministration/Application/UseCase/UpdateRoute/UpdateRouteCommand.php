<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Application\UseCase\UpdateRoute;

final readonly class UpdateRouteCommand
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $code,
        public readonly ?string $description,
        public readonly string  $actorUserId,
    ) {}
}
