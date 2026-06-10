<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Application/UseCase/DeactivateLaboratory/DeactivateLaboratoryCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Application\UseCase\DeactivateLaboratory;

final readonly class DeactivateLaboratoryCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
