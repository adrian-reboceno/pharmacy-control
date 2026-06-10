<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/UseCase/UpdateLaboratory/UpdateLaboratoryCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\UseCase\UpdateLaboratory;

final readonly class UpdateLaboratoryCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $countryCode,
        public readonly ?string $website,
        public readonly string $actorUserId,
    ) {}
}
