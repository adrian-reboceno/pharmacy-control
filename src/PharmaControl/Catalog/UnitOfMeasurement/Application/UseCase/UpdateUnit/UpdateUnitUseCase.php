<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\UpdateUnit;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitNameException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitSymbolException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\UnitNotFoundException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;

final class UpdateUnitUseCase
{
    public function __construct(
        private readonly UnitRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(UpdateUnitCommand $command): UnitDTO
    {
        $unit = $this->repository->findById(new UnitId($command->id));
        if ($unit === null) {
            throw new UnitNotFoundException($command->id);
        }

        $name   = new UnitName($command->name);
        $symbol = new UnitSymbol($command->symbol);
        $type   = UnitType::from($command->type);

        $existingByName = $this->repository->findByName($name);
        if ($existingByName !== null && !$existingByName->getId()->equals($unit->getId())) {
            throw new DuplicateUnitNameException($command->name);
        }

        $existingBySymbol = $this->repository->findBySymbol($symbol);
        if ($existingBySymbol !== null && !$existingBySymbol->getId()->equals($unit->getId())) {
            throw new DuplicateUnitSymbolException($command->symbol);
        }

        $unit->update($name, $symbol, $type);
        $this->repository->save($unit);

        foreach ($unit->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return UnitDTO::fromDomain($unit);
    }
}
