<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Controller;

use PharmaControl\Catalog\UnitOfMeasurement\Application\DTO\UnitDTO;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\CreateUnit\CreateUnitCommand;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\CreateUnit\CreateUnitUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\DeactivateUnit\DeactivateUnitCommand;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\DeactivateUnit\DeactivateUnitUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\GetUnits\GetUnitsQuery;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\GetUnits\GetUnitsUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\UpdateUnit\UpdateUnitCommand;
use PharmaControl\Catalog\UnitOfMeasurement\Application\UseCase\UpdateUnit\UpdateUnitUseCase;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Contract\Repository\UnitRepositoryContract;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Exception\UnitNotFoundException;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;

final class UnitController
{
    public function __construct(
        private readonly CreateUnitUseCase     $create,
        private readonly UpdateUnitUseCase     $update,
        private readonly DeactivateUnitUseCase $deactivate,
        private readonly GetUnitsUseCase       $get,
        private readonly UnitRepositoryContract $repository,
    ) {}

    public function index(array $data): array
    {
        return ($this->get)(new GetUnitsQuery(
            type:     $data['type'] ?? null,
            search:   $data['search'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            perPage:  (int) ($data['per_page'] ?? 20),
            page:     (int) ($data['page'] ?? 1),
        ));
    }

    public function store(array $data): UnitDTO
    {
        return ($this->create)(new CreateUnitCommand(
            name:        $data['name'],
            symbol:      $data['symbol'],
            type:        $data['type'],
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): UnitDTO
    {
        $unit = $this->repository->findById(new UnitId($id));
        if ($unit === null) {
            throw new UnitNotFoundException($id);
        }

        return UnitDTO::fromDomain($unit);
    }

    public function update(string $id, array $data): UnitDTO
    {
        return ($this->update)(new UpdateUnitCommand(
            id:          $id,
            name:        $data['name'],
            symbol:      $data['symbol'],
            type:        $data['type'],
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateUnitCommand($id, $actorUserId));
    }
}
