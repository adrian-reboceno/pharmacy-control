<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Application\UseCase\GetPresentations;

final readonly class GetPresentationsQuery
{
    public function __construct(
        public readonly ?string $search   = null,
        public readonly ?bool   $isActive = null,
        public readonly int     $perPage  = 20,
        public readonly int     $page     = 1,
    ) {}
}
