<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\CreateUnit;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitNameException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\DuplicateUnitSymbolException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;

final class CreateUnitUseCase
{
    public function __construct(
        private readonly UnitRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(CreateUnitCommand $command): UnitDTO
    {
        $name = new UnitName($command->name);
        $symbol = new UnitSymbol($command->symbol);
        $type = UnitType::from($command->type);

        if ($this->repository->findByName($name) !== null) {
            throw new DuplicateUnitNameException($command->name);
        }

        if ($this->repository->findBySymbol($symbol) !== null) {
            throw new DuplicateUnitSymbolException($command->symbol);
        }

        $unit = UnitOfMeasurement::create(
            UnitId::generate(),
            $name,
            $symbol,
            $type,
            new UserId($command->actorUserId),
        );

        $this->repository->save($unit);

        foreach ($unit->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return UnitDTO::fromDomain($unit);
    }
}
