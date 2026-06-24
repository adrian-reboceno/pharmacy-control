<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\ValueObject;

final readonly class StockConfig
{
    public function __construct(
        public readonly int $minStock,
        public readonly int $maxStock,
        public readonly int $expiryAlertDays,
        public readonly bool $manageLots,
        public readonly bool $allowFraction,
    ) {
        if ($minStock < 0) {
            throw new \InvalidArgumentException('El stock mínimo no puede ser negativo.');
        }
        if ($maxStock <= $minStock) {
            throw new \InvalidArgumentException('El stock máximo debe ser mayor al stock mínimo.');
        }
        if ($expiryAlertDays < 1) {
            throw new \InvalidArgumentException('Los días de alerta de vencimiento deben ser >= 1.');
        }
    }
}
