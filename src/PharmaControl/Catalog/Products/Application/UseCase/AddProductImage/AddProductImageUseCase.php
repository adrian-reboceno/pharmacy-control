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

        return ProductDTO::fromDomain($product);
    }
}
