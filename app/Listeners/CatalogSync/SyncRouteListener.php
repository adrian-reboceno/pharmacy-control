<?php

declare(strict_types=1);

namespace App\Listeners\CatalogSync;

use App\Jobs\CatalogSync\SyncRouteToMongoJob;
use PharmaControl\Catalog\RoutesOfAdministration\Application\DTO\RouteDTO;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Contract\Repository\RouteRepositoryContract;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteCreated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteDeactivated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteUpdated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;

final class SyncRouteListener
{
    public function __construct(
        private readonly RouteRepositoryContract $repository,
    ) {}

    public function handleCreated(RouteCreated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleUpdated(RouteUpdated $event): void
    {
        $this->dispatchSync($event->id);
    }

    public function handleDeactivated(RouteDeactivated $event): void
    {
        $this->dispatchSync($event->id);
    }

    private function dispatchSync(RouteId $id): void
    {
        $route = $this->repository->findById($id);

        if ($route === null) {
            return;
        }

        $dto = RouteDTO::fromDomain($route);

        SyncRouteToMongoJob::dispatch([
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
