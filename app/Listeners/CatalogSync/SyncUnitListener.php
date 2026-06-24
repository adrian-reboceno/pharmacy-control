<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncUnitToMongoJob;
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitCreated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitDeactivated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Event\UnitUpdated;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;

final class SyncUnitListener
{
    public function __construct(
        private readonly UnitRepositoryContract $repository,
    ) {}

    public function handleCreated(UnitCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(UnitUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(UnitDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(UnitId $id): void
    {
        $unit = $this->repository->findById($id);

        if ($unit === null) {
            return;
        }

        $dto = UnitDTO::fromDomain($unit);

        SyncUnitToMongoJob::dispatch([
            'id' => $dto->id,
            'name' => $dto->name,
            'symbol' => $dto->symbol,
            'type' => $dto->type,
            'type_label' => $dto->typeLabel,
            'is_active' => $dto->isActive,
            'created_by' => $dto->createdBy,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ]);
    }
}
