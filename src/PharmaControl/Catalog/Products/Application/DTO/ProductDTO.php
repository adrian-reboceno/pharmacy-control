<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\DTO;

use PharmaControl\Catalog\Products\Domain\Model\Product;

final readonly class ProductDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $statusId,
        public readonly string $categoryId,
        public readonly ?string $laboratoryId,
        public readonly string $saleCondition,
        public readonly string $saleConditionLabel,
        public readonly ?string $sanitaryReg,
        public readonly ?string $barcode,
        public readonly ProductSpecsDTO $specs,
        public readonly StockConfigDTO $stockConfig,
        public readonly ProductMarginsDTO $margins,
        /** @var ProductIngredientDTO[] */
        public readonly array $ingredients,
        public readonly array $imageUrls,
        /** @var ProductImageDTO[] */
        public readonly array $images,
        public readonly bool $isActive,
        public readonly ?string $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

    public static function fromDomain(Product $p): self
    {
        return new self(
            id: $p->getId()->value,
            type: $p->getType()->value,
            typeLabel: $p->getType()->label(),
            name: $p->getName()->value,
            description: $p->getDescription(),
            statusId: $p->getStatusId()->value,
            categoryId: $p->getCategoryId()->value,
            laboratoryId: $p->getLaboratoryId()?->value,
            saleCondition: $p->getSaleCondition()->value,
            saleConditionLabel: $p->getSaleCondition()->label(),
            sanitaryReg: $p->getSanitaryReg()?->value,
            barcode: $p->getBarcode()?->value,
            specs: ProductSpecsDTO::fromDomain($p->getSpecs()),
            stockConfig: StockConfigDTO::fromDomain($p->getStockConfig()),
            margins: ProductMarginsDTO::fromDomain($p->getMargins()),
            ingredients: array_map(
                fn ($i) => ProductIngredientDTO::fromEntity($i),
                $p->getIngredients()
            ),
            imageUrls: $p->getImageUrls(),
            images: [],
            isActive: $p->isActive(),
            createdBy: $p->getCreatedBy()?->value,
            createdAt: $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $p->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
