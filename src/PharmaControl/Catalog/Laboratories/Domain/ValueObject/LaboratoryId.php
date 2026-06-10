<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Laboratories/Domain/ValueObject/LaboratoryId.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Laboratories\Domain\ValueObject;

use PharmaControl\Shared\ValueObject\Uuid;

final readonly class LaboratoryId extends Uuid {}
