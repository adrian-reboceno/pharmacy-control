<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\CreateLocation;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Exception\DuplicateLocationNameException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationMaxDepthException;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotFoundException;
use PharmaControl\Catalog\Location\Domain\Model\Location;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;

final class CreateLocationUseCase
{
    public function __construct(
        private readonly LocationRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(CreateLocationCommand $cmd): LocationDTO
    {
        $level = LocationLevel::from($cmd->level);
        $name = new LocationName($cmd->name);

        $parentId = null;
        if ($cmd->parentId !== null) {
            $parentId = new LocationId($cmd->parentId);
            $parent = $this->repository->findById($parentId);

            if ($parent === null) {
                throw new LocationNotFoundException($cmd->parentId);
            }

            $expectedChildLevel = $parent->getLevel()->childLevel();
            if ($expectedChildLevel === null || $level !== $expectedChildLevel) {
                throw new LocationMaxDepthException(
                    "El nivel '{$level->label()}' no es válido como hijo de '{$parent->getLevel()->label()}'. "
                    ."Se esperaba: '{$expectedChildLevel?->label()}'."
                );
            }
        } else {
            if ($level !== LocationLevel::ZONA) {
                throw new LocationMaxDepthException(
                    "Solo las Zonas (level=1) pueden crearse sin padre. Nivel recibido: '{$level->label()}'."
                );
            }
        }

        if ($this->repository->findByNameAndParent($name, $parentId) !== null) {
            throw new DuplicateLocationNameException($name->value);
        }

        $location = Location::create(
            LocationId::generate(),
            $level,
            $parentId,
            $name,
            $cmd->description,
            new UserId($cmd->actorUserId),
        );

        $this->repository->save($location);

        foreach ($location->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return $this->repository->findByIdAsDTO($location->getId())
            ?? LocationDTO::fromDomain($location, $location->getName()->value);
    }
}
