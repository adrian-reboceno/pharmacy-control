<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\DTO;

use PharmaControl\Catalog\Products\Domain\ValueObject\ProductMargins;

final readonly class ProductMarginsDTO
{
    public function __construct(
        public readonly float $retailMargin,
        public readonly float $wholesaleMargin,
    ) {}

    public static function fromDomain(ProductMargins $margins): self
    {
        return new self(
            retailMargin: $margins->retailMargin,
            wholesaleMargin: $margins->wholesaleMargin,
        );
    }
}
