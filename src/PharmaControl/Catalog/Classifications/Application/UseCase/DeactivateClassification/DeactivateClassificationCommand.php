<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Application/UseCase/DeactivateClassification/DeactivateClassificationCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Application\UseCase\DeactivateClassification;

final readonly class DeactivateClassificationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $actorUserId,
    ) {}
}
