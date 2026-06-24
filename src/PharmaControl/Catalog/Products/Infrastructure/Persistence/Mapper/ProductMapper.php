<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Products\Domain\Entity\ProductIngredient;
use PharmaControl\Catalog\Products\Domain\Model\Product;
use PharmaControl\Catalog\Products\Domain\ValueObject\Barcode;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductMargins;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductSpecs;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductType;
use PharmaControl\Catalog\Products\Domain\ValueObject\SaleCondition;
use PharmaControl\Catalog\Products\Domain\ValueObject\SanitaryReg;
use PharmaControl\Catalog\Products\Domain\ValueObject\StockConfig;
use PharmaControl\Catalog\Products\Infrastructure\Persistence\Eloquent\Model\EloquentProduct;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;

final class ProductMapper
{
    public function toDomain(EloquentProduct $model): Product
    {
        $ingredients = $model->ingredients->map(
            fn ($ing) => new ProductIngredient(
                ingredientId: $ing->id,
                concentration: $ing->pivot->concentration,
                concentrationUnit: $ing->pivot->concentration_unit,
            )
        )->all();

        return Product::reconstitute(
            id: new ProductId($model->id),
            type: ProductType::from($model->type),
            name: new ProductName($model->name),
            description: $model->description,
            statusId: new StatusId($model->status_id),
            categoryId: new CategoryId($model->category_id),
            laboratoryId: $model->laboratory_id !== null ? new LaboratoryId($model->laboratory_id) : null,
            saleCondition: SaleCondition::from($model->sale_condition),
            sanitaryReg: $model->sanitary_reg !== null ? new SanitaryReg($model->sanitary_reg) : null,
            barcode: $model->barcode !== null ? new Barcode($model->barcode) : null,
            specs: new ProductSpecs(
                unitId: $model->unit_id,
                presentationId: $model->presentation_id,
                routeId: $model->route_id,
                unitsPerBox: $model->units_per_box,
                unitsPerBlister: $model->units_per_blister,
                locationId: $model->location_id,
            ),
            stockConfig: new StockConfig(
                minStock: $model->min_stock,
                maxStock: $model->max_stock,
                expiryAlertDays: $model->expiry_alert_days,
                manageLots: $model->manage_lots,
                allowFraction: $model->allow_fraction,
            ),
            margins: new ProductMargins(
                retailMargin: $model->retail_margin,
                wholesaleMargin: $model->wholesale_margin,
            ),
            ingredients: $ingredients,
            imageUrls: $model->images->pluck('url')->all(),
            isActive: $model->is_active,
            createdBy: $model->created_by !== null ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toPersistence(Product $p): array
    {
        return [
            'id' => $p->getId()->value,
            'type' => $p->getType()->value,
            'name' => $p->getName()->value,
            'description' => $p->getDescription(),
            'sale_condition' => $p->getSaleCondition()->value,
            'sanitary_reg' => $p->getSanitaryReg()?->value,
            'barcode' => $p->getBarcode()?->value,
            'status_id' => $p->getStatusId()->value,
            'category_id' => $p->getCategoryId()->value,
            'laboratory_id' => $p->getLaboratoryId()?->value,
            'unit_id' => $p->getSpecs()->unitId,
            'presentation_id' => $p->getSpecs()->presentationId,
            'route_id' => $p->getSpecs()->routeId,
            'location_id' => $p->getSpecs()->locationId,
            'units_per_box' => $p->getSpecs()->unitsPerBox,
            'units_per_blister' => $p->getSpecs()->unitsPerBlister,
            'min_stock' => $p->getStockConfig()->minStock,
            'max_stock' => $p->getStockConfig()->maxStock,
            'expiry_alert_days' => $p->getStockConfig()->expiryAlertDays,
            'manage_lots' => $p->getStockConfig()->manageLots,
            'allow_fraction' => $p->getStockConfig()->allowFraction,
            'retail_margin' => $p->getMargins()->retailMargin,
            'wholesale_margin' => $p->getMargins()->wholesaleMargin,
            'is_active' => $p->isActive(),
            'created_by' => $p->getCreatedBy()?->value,
        ];
    }
}
