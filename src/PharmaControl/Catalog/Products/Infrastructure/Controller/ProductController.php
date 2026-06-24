<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Infrastructure\Controller;

use Illuminate\Support\Facades\DB;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Application\UseCase\AddProductImage\AddProductImageCommand;
use PharmaControl\Catalog\Products\Application\UseCase\AddProductImage\AddProductImageUseCase;
use PharmaControl\Catalog\Products\Application\UseCase\CreateProduct\CreateProductCommand;
use PharmaControl\Catalog\Products\Application\UseCase\CreateProduct\CreateProductUseCase;
use PharmaControl\Catalog\Products\Application\UseCase\DeactivateProduct\DeactivateProductCommand;
use PharmaControl\Catalog\Products\Application\UseCase\DeactivateProduct\DeactivateProductUseCase;
use PharmaControl\Catalog\Products\Application\UseCase\GetProduct\GetProductQuery;
use PharmaControl\Catalog\Products\Application\UseCase\GetProduct\GetProductUseCase;
use PharmaControl\Catalog\Products\Application\UseCase\GetProducts\GetProductsQuery;
use PharmaControl\Catalog\Products\Application\UseCase\GetProducts\GetProductsUseCase;
use PharmaControl\Catalog\Products\Application\UseCase\UpdateProduct\UpdateProductCommand;
use PharmaControl\Catalog\Products\Application\UseCase\UpdateProduct\UpdateProductUseCase;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Exception\ProductImageNotFoundException;
use PharmaControl\Catalog\Products\Domain\Event\ProductUpdated;
use PharmaControl\Catalog\Products\Domain\Exception\ProductNotFoundException;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Infrastructure\Storage\ProductImageStorage;

final class ProductController
{
    public function __construct(
        private readonly CreateProductUseCase     $create,
        private readonly UpdateProductUseCase     $update,
        private readonly DeactivateProductUseCase $deactivate,
        private readonly GetProductsUseCase       $getAll,
        private readonly GetProductUseCase        $getOne,
        private readonly AddProductImageUseCase   $addImage,
        private readonly ProductRepositoryContract $repository,
        private readonly ProductImageStorage      $imageStorage,
        private readonly EventPublisherContract   $events,
    ) {}

    public function index(array $data): array
    {
        return ($this->getAll)(new GetProductsQuery(
            search:        $data['search'] ?? null,
            type:          $data['type'] ?? null,
            statusId:      $data['status_id'] ?? null,
            categoryId:    $data['category_id'] ?? null,
            laboratoryId:  $data['laboratory_id'] ?? null,
            saleCondition: $data['sale_condition'] ?? null,
            manageLots:    isset($data['manage_lots']) ? (bool) $data['manage_lots'] : null,
            isActive:      isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage:       (int) ($data['per_page'] ?? 20),
            page:          (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): ProductDTO
    {
        return ($this->create)(new CreateProductCommand(
            type:            $data['type'],
            name:            $data['name'],
            description:     $data['description'] ?? null,
            statusId:        $data['status_id'],
            categoryId:      $data['category_id'],
            laboratoryId:    $data['laboratory_id'] ?? null,
            saleCondition:   $data['sale_condition'],
            sanitaryReg:     $data['sanitary_reg'] ?? null,
            barcode:         $data['barcode'] ?? null,
            unitId:          $data['unit_id'],
            presentationId:  $data['presentation_id'],
            routeId:         $data['route_id'],
            unitsPerBox:     (int) $data['units_per_box'],
            unitsPerBlister: (int) $data['units_per_blister'],
            locationId:      $data['location_id'] ?? null,
            minStock:        (int) $data['min_stock'],
            maxStock:        (int) $data['max_stock'],
            expiryAlertDays: (int) $data['expiry_alert_days'],
            manageLots:      (bool) $data['manage_lots'],
            allowFraction:   (bool) $data['allow_fraction'],
            retailMargin:    (float) $data['retail_margin'],
            wholesaleMargin: (float) $data['wholesale_margin'],
            ingredients:     $data['ingredients'] ?? [],
            actorUserId:     $data['actor_user_id'],
        ));
    }

    public function show(string $id): ProductDTO
    {
        return ($this->getOne)(new GetProductQuery($id));
    }

    public function update(string $id, array $data): ProductDTO
    {
        return ($this->update)(new UpdateProductCommand(
            id:              $id,
            name:            $data['name'],
            description:     $data['description'] ?? null,
            statusId:        $data['status_id'],
            categoryId:      $data['category_id'],
            laboratoryId:    $data['laboratory_id'] ?? null,
            saleCondition:   $data['sale_condition'],
            sanitaryReg:     $data['sanitary_reg'] ?? null,
            barcode:         $data['barcode'] ?? null,
            unitId:          $data['unit_id'],
            presentationId:  $data['presentation_id'],
            routeId:         $data['route_id'],
            unitsPerBox:     (int) $data['units_per_box'],
            unitsPerBlister: (int) $data['units_per_blister'],
            locationId:      $data['location_id'] ?? null,
            minStock:        (int) $data['min_stock'],
            maxStock:        (int) $data['max_stock'],
            expiryAlertDays: (int) $data['expiry_alert_days'],
            manageLots:      (bool) $data['manage_lots'],
            allowFraction:   (bool) $data['allow_fraction'],
            retailMargin:    (float) $data['retail_margin'],
            wholesaleMargin: (float) $data['wholesale_margin'],
            ingredients:     $data['ingredients'] ?? [],
            actorUserId:     $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateProductCommand($id, $actorUserId));
    }

    public function addImage(string $id, array $data): ProductDTO
    {
        return ($this->addImage)(new AddProductImageCommand(
            productId:    $id,
            imageContent: $data['image_content'],
            mimeType:     $data['mime_type'],
            actorUserId:  $data['actor_user_id'],
        ));
    }

    public function removeImage(string $id, string $imageId, string $actorUserId): void
    {
        $product = $this->repository->findById(new ProductId($id));
        if ($product === null) {
            throw new ProductNotFoundException($id);
        }

        $url = $this->imageStorage->findUrlById($imageId);
        if ($url === null) {
            throw new ProductImageNotFoundException($imageId);
        }

        $this->imageStorage->delete($imageId);
        $product->removeImage($url);
        $this->repository->save($product);

        foreach ($product->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }

    public function reorderImages(string $id, array $imageIds): void
    {
        $product = $this->repository->findById(new ProductId($id));
        if ($product === null) {
            throw new ProductNotFoundException($id);
        }

        foreach ($imageIds as $index => $imageId) {
            DB::table('product_images')
                ->where('id', $imageId)
                ->where('product_id', $id)
                ->update([
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
        }

        $this->events->publish(new ProductUpdated(
            new ProductId($id),
            ['images' => 'reordered'],
            new \DateTimeImmutable,
        ));
    }
}
