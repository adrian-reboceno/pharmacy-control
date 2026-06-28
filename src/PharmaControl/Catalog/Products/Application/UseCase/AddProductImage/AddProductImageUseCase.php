<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\AddProductImage;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Exception\ProductNotFoundException;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Infrastructure\Storage\ProductImageStorage;

final class AddProductImageUseCase
{
    public function __construct(
        private readonly ProductRepositoryContract $productRepo,
        private readonly ProductImageStorage $imageStorage,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(AddProductImageCommand $cmd): ProductDTO
    {
        $product = $this->productRepo->findById(new ProductId($cmd->productId));
        if ($product === null) {
            throw new ProductNotFoundException($cmd->productId);
        }

        if (count($product->getImageUrls()) >= 10) {
            throw new \DomainException('El producto ya tiene el máximo de 10 imágenes.');
        }

        $url = $this->imageStorage->store($cmd->productId, $cmd->imageContent, $cmd->mimeType);
        $product->addImage($url);

        $this->productRepo->save($product);

        foreach ($product->releaseEvents() as $event) {
            $this->events->publish($event);
        }

       return $this->buildEnrichedDTO($cmd->productId, $product);
    }
    private function buildEnrichedDTO(string $productId, \PharmaControl\Catalog\Products\Domain\Model\Product $product): ProductDTO
    {
        $dto = ProductDTO::fromDomain($product);

        $images = \Illuminate\Support\Facades\DB::table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get(['id', 'url', 'is_primary', 'sort_order'])
            ->map(fn($img) => new \PharmaControl\Catalog\Products\Application\DTO\ProductImageDTO(
                id:        $img->id,
                url:       $img->url,
                isPrimary: (bool) $img->is_primary,
                sortOrder: (int)  $img->sort_order,
            ))->all();

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
            ingredients:        $dto->ingredients,
            imageUrls:          $dto->imageUrls,
            images:             $images,
            isActive:           $dto->isActive,
            createdBy:          $dto->createdBy,
            createdAt:          $dto->createdAt,
            updatedAt:          $dto->updatedAt,
        );
    }
}
