<?php

declare(strict_types=1);

namespace PharmaControl\Suppliers\Application\UseCase\GetSuppliers;

final readonly class GetSuppliersQuery
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $type = null,
        public readonly ?bool $isActive = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
