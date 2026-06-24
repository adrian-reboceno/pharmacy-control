<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Application\UseCase\DeactivateProduct;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Exception\ProductNotFoundException;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;

final class DeactivateProductUseCase
{
    public function __construct(
        private readonly ProductRepositoryContract $productRepo,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateProductCommand $cmd): void
    {
        $product = $this->productRepo->findById(new ProductId($cmd->id));
        if ($product === null) {
            throw new ProductNotFoundException($cmd->id);
        }

        $product->deactivate();
        $this->productRepo->save($product);

        foreach ($product->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
