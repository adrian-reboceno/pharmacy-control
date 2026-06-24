<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\UpdateLocation;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Exception\DuplicateLocationNameException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotFoundException;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;

final class UpdateLocationUseCase
{
    public function __construct(
        private readonly LocationRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(UpdateLocationCommand $cmd): LocationDTO
    {
        $id = new LocationId($cmd->id);
        $location = $this->repository->findById($id);

        if ($location === null) {
            throw new LocationNotFoundException($cmd->id);
        }

        $newName = new LocationName($cmd->name);

        $sibling = $this->repository->findByNameAndParent($newName, $location->getParentId());
        if ($sibling !== null && $sibling->getId()->value !== $location->getId()->value) {
            throw new DuplicateLocationNameException($newName->value);
        }

        $location->update($newName, $cmd->description);

        $this->repository->save($location);

        foreach ($location->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return $this->repository->findByIdAsDTO($id)
            ?? LocationDTO::fromDomain($location, $location->getName()->value);
    }
}
