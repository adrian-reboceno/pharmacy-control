<?php

declare(strict_types=1);

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Application\DTO\ProductIngredientDTO;

class ProductResource extends JsonResource
{
    public function __construct(ProductDTO $resource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var ProductDTO $dto */
        $dto = $this->resource;

        return [
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
                fn (ProductIngredientDTO $i) => [
                    'ingredient_id' => $i->ingredientId,
                    'concentration' => $i->concentration,
                    'concentration_unit' => $i->concentrationUnit,
                ],
                $dto->ingredients
            ),
            'image_urls' => $dto->imageUrls,
            'is_active' => $dto->isActive,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
