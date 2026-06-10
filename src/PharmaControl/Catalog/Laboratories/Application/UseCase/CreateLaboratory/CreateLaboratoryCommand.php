<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/UseCase/CreateLaboratory/CreateLaboratoryCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\UseCase\CreateLaboratory;

final readonly class CreateLaboratoryCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $countryCode,
        public readonly ?string $website,
        public readonly string $actorUserId,
    ) {}
}
