<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\CreateProduct;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotFoundException;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Entity\ProductIngredient;
use PharmaControl\Catalog\Products\Domain\Exception\BrandedProductRequiresLaboratoryException;
use PharmaControl\Catalog\Products\Domain\Exception\DuplicateBarcodeException;
use PharmaControl\Catalog\Products\Domain\Exception\DuplicateProductNameException;
use PharmaControl\Catalog\Products\Domain\Exception\LocationMustBePositionException;
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
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;

final class CreateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryContract  $productRepo,
        private readonly LocationRepositoryContract $locationRepo,
        private readonly EventPublisherContract     $events,
    ) {}

    public function __invoke(CreateProductCommand $cmd): ProductDTO
    {
        $type = ProductType::from($cmd->type);

        if ($type->requiresLaboratory() && $cmd->laboratoryId === null) {
            throw new BrandedProductRequiresLaboratoryException;
        }

        $name = new ProductName($cmd->name);
        if ($this->productRepo->findByName($name) !== null) {
            throw new DuplicateProductNameException($cmd->name);
        }

        $barcode = null;
        if ($cmd->barcode !== null) {
            $barcode = new Barcode($cmd->barcode);
            if ($this->productRepo->findByBarcode($barcode) !== null) {
                throw new DuplicateBarcodeException($cmd->barcode);
            }
        }

        if ($cmd->locationId !== null) {
            $location = $this->locationRepo->findById(new LocationId($cmd->locationId));
            if ($location === null) {
                throw new LocationNotFoundException($cmd->locationId);
            }
            if (! $location->isLeaf()) {
                throw new LocationMustBePositionException($cmd->locationId);
            }
        }

        $ingredients = array_map(
            fn (array $i) => new ProductIngredient(
                ingredientId:      $i['ingredient_id'],
                concentration:     $i['concentration'],
                concentrationUnit: $i['concentration_unit'],
            ),
            $cmd->ingredients
        );

        $product = Product::create(
            id:            ProductId::generate(),
            type:          $type,
            name:          $name,
            description:   $cmd->description,
            statusId:      new StatusId($cmd->statusId),
            categoryId:    new CategoryId($cmd->categoryId),
            laboratoryId:  $cmd->laboratoryId !== null ? new LaboratoryId($cmd->laboratoryId) : null,
            saleCondition: SaleCondition::from($cmd->saleCondition),
            sanitaryReg:   $cmd->sanitaryReg !== null ? new SanitaryReg($cmd->sanitaryReg) : null,
            barcode:       $barcode,
            specs:         new ProductSpecs(
                unitId:          $cmd->unitId,
                presentationId:  $cmd->presentationId,
                routeId:         $cmd->routeId,
                unitsPerBox:     $cmd->unitsPerBox,
                unitsPerBlister: $cmd->unitsPerBlister,
                locationId:      $cmd->locationId,
            ),
            stockConfig:   new StockConfig(
                minStock:        $cmd->minStock,
                maxStock:        $cmd->maxStock,
                expiryAlertDays: $cmd->expiryAlertDays,
                manageLots:      $cmd->manageLots,
                allowFraction:   $cmd->allowFraction,
            ),
            margins:       new ProductMargins(
                retailMargin:    $cmd->retailMargin,
                wholesaleMargin: $cmd->wholesaleMargin,
            ),
            ingredients:   $ingredients,
            createdBy:     new UserId($cmd->actorUserId),
        );

        $this->productRepo->save($product);

        foreach ($product->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return ProductDTO::fromDomain($product);
    }
}
