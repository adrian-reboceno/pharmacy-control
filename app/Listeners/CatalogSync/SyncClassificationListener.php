<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncClassificationToMongoJob;
use PharmaControl\Catalog\Classifications\Application\DTO\ClassificationDTO;
use PharmaControl\Catalog\Classifications\Domain\Contract\Repository\ClassificationRepositoryContract;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationCreated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationDeactivated;
use PharmaControl\Catalog\Classifications\Domain\Event\ClassificationUpdated;
use PharmaControl\Catalog\Classifications\Domain\ValueObject\ClassificationId;

final class SyncClassificationListener
{
    public function __construct(
        private readonly ClassificationRepositoryContract $repository,
    ) {}

    public function handleCreated(ClassificationCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(ClassificationUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(ClassificationDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(ClassificationId $id): void
    {
        $classification = $this->repository->findById($id);

        if ($classification === null) {
            return;
        }

        $dto = ClassificationDTO::fromDomain($classification);

        SyncClassificationToMongoJob::dispatch([
            'id'                     => $dto->id,
            'lgs_group'              => $dto->lgsGroup,
            'lgs_group_label'        => $dto->lgsGroupLabel,
            'name'                   => $dto->name,
            'prescription_type'      => $dto->prescriptionType,
            'prescription_type_label' => $dto->prescriptionTypeLabel,
            'validity_days'          => $dto->validityDays,
            'validity_note'          => $dto->validityNote,
            'is_controlled'          => $dto->isControlled,
            'is_active'              => $dto->isActive,
            'created_by'             => $dto->createdBy,
            'created_at'             => $dto->createdAt,
            'updated_at'             => $dto->updatedAt,
        ]);
    }
}
