<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Application\DTO;

use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;

final readonly class StatusDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromDomain(ProductStatus $s): self
    {
        return new self(
            id: $s->getId()->value,
            name: $s->getName()->value,
            code: $s->getCode()->value,
            description: $s->getDescription(),
            isActive: $s->isActive(),
            createdBy: $s->getCreatedBy()?->value,
            createdAt: $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $s->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
