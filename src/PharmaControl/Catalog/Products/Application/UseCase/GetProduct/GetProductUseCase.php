<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\GetProduct;

use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Exception\ProductNotFoundException;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;

final class GetProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryContract $productRepo,
    ) {}

    public function __invoke(GetProductQuery $query): ProductDTO
    {
         $product = $this->productRepo->findById(new ProductId($query->id));
        if ($product === null) {
            throw new ProductNotFoundException($query->id);
        }

        $dto = ProductDTO::fromDomain($product);

        // Enriquecer con imágenes completas desde BD
        $images = \Illuminate\Support\Facades\DB::table('product_images')
            ->where('product_id', $query->id)
            ->orderBy('sort_order')
            ->get(['id', 'url', 'is_primary', 'sort_order'])
            ->map(fn($img) => new \PharmaControl\Catalog\Products\Application\DTO\ProductImageDTO(
                id:        $img->id,
                url:       $img->url,
                isPrimary: (bool) $img->is_primary,
                sortOrder: (int)  $img->sort_order,
            ))->all();
        $ingredientNames = \Illuminate\Support\Facades\DB::table('active_ingredients')
            ->whereIn('id', array_map(fn($i) => $i->ingredientId, $dto->ingredients))
            ->pluck('name', 'id');
        
        $ingredients = array_map(
            fn($i) => new \PharmaControl\Catalog\Products\Application\DTO\ProductIngredientDTO(
                ingredientId:      $i->ingredientId,
                ingredientName:    $ingredientNames[$i->ingredientId] ?? '',
                concentration:     $i->concentration,
                concentrationUnit: $i->concentrationUnit,
            ),
            $dto->ingredients
        );
        return new ProductDTO(
            id:                 $dto->id,
            type:               $dto->type,
            typeLabel:          $dto->typeLabel,
            name:               $dto->name,
            description:        $dto->description,
            statusId:           $dto->statusId,
            categoryId:         $dto->categoryId,
            laboratoryId:       $dto->laboratoryId,
            saleCondition:      $dto->saleCondition,
            saleConditionLabel: $dto->saleConditionLabel,
            sanitaryReg:        $dto->sanitaryReg,
            barcode:            $dto->barcode,
            specs:              $dto->specs,
            stockConfig:        $dto->stockConfig,
            margins:            $dto->margins,
            ingredients:        $ingredients,
            imageUrls:          $dto->imageUrls,
            images:             $images,
            isActive:           $dto->isActive,
            createdBy:          $dto->createdBy,
            createdAt:          $dto->createdAt,
            updatedAt:          $dto->updatedAt,
        );
    }
}
