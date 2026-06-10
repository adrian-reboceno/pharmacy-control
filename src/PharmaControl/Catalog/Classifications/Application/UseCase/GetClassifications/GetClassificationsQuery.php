<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Application/UseCase/GetClassifications/GetClassificationsQuery.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Application\UseCase\GetClassifications;

final readonly class GetClassificationsQuery
{
    public function __construct(
        public readonly ?bool $isActive = null,
        public readonly ?bool $isControlled = null,
        public readonly int $perPage = 20,
        public readonly int $page = 1,
    ) {}
}
