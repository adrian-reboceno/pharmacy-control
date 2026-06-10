<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Classifications/Application/UseCase/UpdateClassification/UpdateClassificationCommand.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Classifications\Application\UseCase\UpdateClassification;

final readonly class UpdateClassificationCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $prescriptionType,
        public readonly ?int $validityDays,
        public readonly ?string $validityNote,
        public readonly string $actorUserId,
    ) {}
}
