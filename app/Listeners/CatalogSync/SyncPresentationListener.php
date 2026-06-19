<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncPresentationToMongoJob;
use PharmaControl\Catalog\Presentations\Application\DTO\PresentationDTO;
use PharmaControl\Catalog\Presentations\Domain\Contract\Repository\PresentationRepositoryContract;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationCreated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationDeactivated;
use PharmaControl\Catalog\Presentations\Domain\Event\PresentationUpdated;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;

final class SyncPresentationListener
{
    public function __construct(
        private readonly PresentationRepositoryContract $repository,
    ) {}

    public function handleCreated(PresentationCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(PresentationUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(PresentationDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(PresentationId $id): void
    {
        $presentation = $this->repository->findById($id);

        if ($presentation === null) {
            return;
        }

        $dto = PresentationDTO::fromDomain($presentation);

        SyncPresentationToMongoJob::dispatch([
            'id'           => $dto->id,
            'name'         => $dto->name,
            'abbreviation' => $dto->abbreviation,
            'description'  => $dto->description,
            'is_active'    => $dto->isActive,
            'created_by'   => $dto->createdBy,
            'created_at'   => $dto->createdAt,
            'updated_at'   => $dto->updatedAt,
        ]);
    }
}
