<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

final readonly class ProductMargins
{
    public function __construct(
        public readonly float $retailMargin,
        public readonly float $wholesaleMargin,
    ) {
        if ($retailMargin < 0 || $retailMargin > 100) {
            throw new \InvalidArgumentException('El margen retail debe estar entre 0 y 100%.');
        }
        if ($wholesaleMargin < 0 || $wholesaleMargin > 100) {
            throw new \InvalidArgumentException('El margen mayorista debe estar entre 0 y 100%.');
        }
    }

    public function calculateRetailPrice(float $purchasePrice): float
    {
        return round($purchasePrice * (1 + $this->retailMargin / 100), 2);
    }

    public function calculateWholesalePrice(float $purchasePrice): float
    {
        return round($purchasePrice * (1 + $this->wholesaleMargin / 100), 2);
    }
}
