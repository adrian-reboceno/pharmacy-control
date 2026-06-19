<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncLaboratoryToMongoJob;
use PharmaControl\Catalog\Laboratories\Application\DTO\LaboratoryDTO;
use PharmaControl\Catalog\Laboratories\Domain\Contract\Repository\LaboratoryRepositoryContract;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryCreated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryDeactivated;
use PharmaControl\Catalog\Laboratories\Domain\Event\LaboratoryUpdated;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;

final class SyncLaboratoryListener
{
    public function __construct(
        private readonly LaboratoryRepositoryContract $repository,
    ) {}

    public function handleCreated(LaboratoryCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(LaboratoryUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(LaboratoryDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(LaboratoryId $id): void
    {
        $laboratory = $this->repository->findById($id);

        if ($laboratory === null) {
            return;
        }

        $dto = LaboratoryDTO::fromDomain($laboratory);

        SyncLaboratoryToMongoJob::dispatch([
            'id'           => $dto->id,
            'name'         => $dto->name,
            'country_code' => $dto->countryCode,
            'website'      => $dto->website,
            'is_active'    => $dto->isActive,
            'created_by'   => $dto->createdBy,
            'created_at'   => $dto->createdAt,
            'updated_at'   => $dto->updatedAt,
        ]);
    }
}
