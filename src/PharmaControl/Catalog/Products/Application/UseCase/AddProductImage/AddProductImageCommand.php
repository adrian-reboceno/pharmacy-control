<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\AddProductImage;

final readonly class AddProductImageCommand
{
    public function __construct(
        public readonly string $productId,
        public readonly string $imageContent,
        public readonly string $mimeType,
        public readonly string $actorUserId,
    ) {}
}
