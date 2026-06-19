<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncCategoryToMongoJob;
use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryCreated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryDeactivated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryUpdated;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;

final class SyncCategoryListener
{
    public function __construct(
        private readonly CategoryRepositoryContract $repository,
    ) {}

    public function handleCreated(CategoryCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(CategoryUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(CategoryDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(CategoryId $id): void
    {
        $category = $this->repository->findById($id);

        if ($category === null) {
            return;
        }

        $dto = CategoryDTO::fromDomain($category);

        SyncCategoryToMongoJob::dispatch([
            'id'          => $dto->id,
            'parent_id'   => $dto->parentId,
            'name'        => $dto->name,
            'slug'        => $dto->slug,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
            'is_root'     => $dto->isRoot,
            'created_by'  => $dto->createdBy,
            'created_at'  => $dto->createdAt,
            'updated_at'  => $dto->updatedAt,
        ]);
    }
}
