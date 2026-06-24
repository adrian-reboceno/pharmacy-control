<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\DTO;

use PharmaControl\Catalog\Products\Domain\ValueObject\StockConfig;

final readonly class StockConfigDTO
{
    public function __construct(
        public readonly int  $minStock,
        public readonly int  $maxStock,
        public readonly int  $expiryAlertDays,
        public readonly bool $manageLots,
        public readonly bool $allowFraction,
    ) {}

    public static function fromDomain(StockConfig $config): self
    {
        return new self(
            minStock:        $config->minStock,
            maxStock:        $config->maxStock,
            expiryAlertDays: $config->expiryAlertDays,
            manageLots:      $config->manageLots,
            allowFraction:   $config->allowFraction,
        );
    }
}
