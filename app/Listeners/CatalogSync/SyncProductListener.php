<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncProductToMongoJob;
use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Event\ProductCreated;
use PharmaControl\Catalog\Products\Domain\Event\ProductDeactivated;
use PharmaControl\Catalog\Products\Domain\Event\ProductImageAdded;
use PharmaControl\Catalog\Products\Domain\Event\ProductUpdated;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;

final class SyncProductListener
{
    public function __construct(
        private readonly ProductRepositoryContract $repository,
    ) {}

    public function handleCreated(ProductCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(ProductUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(ProductDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleImageAdded(ProductImageAdded $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(ProductId $id): void
    {
        $product = $this->repository->findById($id);
        if ($product === null) {
            return;
        }

        $dto = ProductDTO::fromDomain($product);

        SyncProductToMongoJob::dispatch([
            'id' => $dto->id,
            'type' => $dto->type,
            'type_label' => $dto->typeLabel,
            'name' => $dto->name,
            'description' => $dto->description,
            'status_id' => $dto->statusId,
            'category_id' => $dto->categoryId,
            'laboratory_id' => $dto->laboratoryId,
            'sale_condition' => $dto->saleCondition,
            'sale_condition_label' => $dto->saleConditionLabel,
            'sanitary_reg' => $dto->sanitaryReg,
            'barcode' => $dto->barcode,
            'specs' => [
                'unit_id' => $dto->specs->unitId,
                'presentation_id' => $dto->specs->presentationId,
                'route_id' => $dto->specs->routeId,
                'units_per_box' => $dto->specs->unitsPerBox,
                'units_per_blister' => $dto->specs->unitsPerBlister,
                'location_id' => $dto->specs->locationId,
            ],
            'stock_config' => [
                'min_stock' => $dto->stockConfig->minStock,
                'max_stock' => $dto->stockConfig->maxStock,
                'expiry_alert_days' => $dto->stockConfig->expiryAlertDays,
                'manage_lots' => $dto->stockConfig->manageLots,
                'allow_fraction' => $dto->stockConfig->allowFraction,
            ],
            'margins' => [
                'retail_margin' => $dto->margins->retailMargin,
                'wholesale_margin' => $dto->margins->wholesaleMargin,
            ],
            'ingredients' => array_map(
                fn ($i) => [
                    'ingredient_id' => $i->ingredientId,
                    'concentration' => $i->concentration,
                    'concentration_unit' => $i->concentrationUnit,
                ],
                $dto->ingredients
            ),
            'image_urls' => $dto->imageUrls,
            'is_active' => $dto->isActive,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ]);
    }
}
