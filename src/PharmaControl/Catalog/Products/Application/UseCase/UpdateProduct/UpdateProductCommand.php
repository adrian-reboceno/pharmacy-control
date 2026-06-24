<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\UpdateProduct;

final readonly class UpdateProductCommand
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly ?string $description,
        public readonly string  $statusId,
        public readonly string  $categoryId,
        public readonly ?string $laboratoryId,
        public readonly string  $saleCondition,
        public readonly ?string $sanitaryReg,
        public readonly ?string $barcode,
        public readonly string  $unitId,
        public readonly string  $presentationId,
        public readonly string  $routeId,
        public readonly int     $unitsPerBox,
        public readonly int     $unitsPerBlister,
        public readonly ?string $locationId,
        public readonly int     $minStock,
        public readonly int     $maxStock,
        public readonly int     $expiryAlertDays,
        public readonly bool    $manageLots,
        public readonly bool    $allowFraction,
        public readonly float   $retailMargin,
        public readonly float   $wholesaleMargin,
        /** @var array<array{ingredient_id: string, concentration: string, concentration_unit: string}> */
        public readonly array   $ingredients,
        public readonly string  $actorUserId,
    ) {}
}
