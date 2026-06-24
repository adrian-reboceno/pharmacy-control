<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncLocationToMongoJob;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Event\LocationCreated;
use PharmaControl\Catalog\Location\Domain\Event\LocationDeactivated;
use PharmaControl\Catalog\Location\Domain\Event\LocationUpdated;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;

final class SyncLocationListener
{
    public function __construct(
        private readonly LocationRepositoryContract $repository,
    ) {}

    public function handleCreated(LocationCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(LocationUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(LocationDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(LocationId $id): void
    {
        $dto = $this->repository->findByIdAsDTO($id);

        if ($dto === null) {
            return;
        }

        SyncLocationToMongoJob::dispatch([
            'id' => $dto->id,
            'name' => $dto->name,
            'level' => $dto->level,
            'level_label' => $dto->levelLabel,
            'parent_id' => $dto->parentId,
            'description' => $dto->description,
            'is_active' => $dto->isActive,
            'is_leaf' => $dto->isLeaf,
            'full_path' => $dto->fullPath,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ]);
    }
}
