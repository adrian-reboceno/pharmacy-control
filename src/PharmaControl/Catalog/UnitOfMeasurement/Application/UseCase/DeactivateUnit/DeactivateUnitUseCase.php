<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\DeactivateUnit;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\UnitNotFoundException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;

final class DeactivateUnitUseCase
{
    public function __construct(
        private readonly UnitRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(DeactivateUnitCommand $command): void
    {
        $unit = $this->repository->findById(new UnitId($command->id));
        if ($unit === null) {
            throw new UnitNotFoundException($command->id);
        }

        $unit->deactivate();
        $this->repository->save($unit);

        foreach ($unit->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
