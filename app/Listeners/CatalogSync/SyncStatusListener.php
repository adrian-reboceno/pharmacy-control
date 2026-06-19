<?php
declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncStatusToMongoJob;
use PharmaControl\Catalog\Status\Application\DTO\StatusDTO;
use PharmaControl\Catalog\Status\Domain\Contract\Repository\StatusRepositoryContract;
use PharmaControl\Catalog\Status\Domain\Event\StatusCreated;
use PharmaControl\Catalog\Status\Domain\Event\StatusDeactivated;
use PharmaControl\Catalog\Status\Domain\Event\StatusUpdated;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;

final class SyncStatusListener
{
    public function __construct(
        private readonly StatusRepositoryContract $repository,
    ) {}

    public function handleCreated(StatusCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(StatusUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(StatusDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    /**
     * Relee el AR completo desde Postgres en lugar de confiar en datos
     * parciales del evento — garantiza que el documento Mongo siempre
     * refleja el estado actual completo.
     */
    private function dispatchSync(StatusId $id): void
    {
        $status = $this->repository->findById($id);

        if ($status === null) {
            return;
        }

        $dto = StatusDTO::fromDomain($status);

        SyncStatusToMongoJob::dispatch([
            'id'          => $dto->id,
            'name'        => $dto->name,
            'code'        => $dto->code,
            'description' => $dto->description,
            'is_active'   => $dto->isActive,
            'created_by'  => $dto->createdBy,
            'created_at'  => $dto->createdAt,
            'updated_at'  => $dto->updatedAt,
        ]);
    }
}