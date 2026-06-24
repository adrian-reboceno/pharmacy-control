<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\DeactivateLocation;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotFoundException;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;

final class DeactivateLocationUseCase
{
    public function __construct(
        private readonly LocationRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateLocationCommand $cmd): void
    {
        $id = new LocationId($cmd->id);
        $location = $this->repository->findById($id);

        if ($location === null) {
            throw new LocationNotFoundException($cmd->id);
        }

        $location->deactivate();

        $this->repository->save($location);

        foreach ($location->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
