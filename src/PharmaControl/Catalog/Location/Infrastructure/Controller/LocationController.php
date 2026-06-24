<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Infrastructure\Controller;

use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Application\UseCase\CreateLocation\CreateLocationCommand;
use PharmaControl\Catalog\Location\Application\UseCase\CreateLocation\CreateLocationUseCase;
use PharmaControl\Catalog\Location\Application\UseCase\DeactivateLocation\DeactivateLocationCommand;
use PharmaControl\Catalog\Location\Application\UseCase\DeactivateLocation\DeactivateLocationUseCase;
use PharmaControl\Catalog\Location\Application\UseCase\GetLocations\GetLocationsQuery;
use PharmaControl\Catalog\Location\Application\UseCase\GetLocations\GetLocationsUseCase;
use PharmaControl\Catalog\Location\Application\UseCase\UpdateLocation\UpdateLocationCommand;
use PharmaControl\Catalog\Location\Application\UseCase\UpdateLocation\UpdateLocationUseCase;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Exception\LocationNotFoundException;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;

final class LocationController
{
    public function __construct(
        private readonly CreateLocationUseCase $create,
        private readonly UpdateLocationUseCase $update,
        private readonly DeactivateLocationUseCase $deactivate,
        private readonly GetLocationsUseCase $getLocations,
        private readonly LocationRepositoryContract $repository,
    ) {}

    /** @return LocationDTO[] */
    public function index(array $data): array
    {
        $level = isset($data['level']) ? (int) $data['level'] : null;
        $isActive = isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        $parentId = $data['parent_id'] ?? null;

        return ($this->getLocations)(new GetLocationsQuery(
            level: $level,
            parentId: $parentId,
            isActive: $isActive,
        ));
    }

    /** @return LocationDTO[] root nodes with nested children */
    public function tree(array $data): array
    {
        $isActive = isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $flat = ($this->getLocations)(new GetLocationsQuery(isActive: $isActive));

        return $this->buildTree($flat);
    }

    /** @return LocationDTO[] only POSICION (level=4) */
    public function leaves(array $data): array
    {
        $isActive = isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        $filters = [];
        if ($isActive !== null) {
            $filters['is_active'] = $isActive;
        }

        return $this->repository->findLeaves($filters);
    }

    public function store(array $data): LocationDTO
    {
        return ($this->create)(new CreateLocationCommand(
            name: $data['name'],
            level: (int) $data['level'],
            parentId: $data['parent_id'] ?? null,
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): LocationDTO
    {
        $dto = $this->repository->findByIdAsDTO(new LocationId($id));

        if ($dto === null) {
            throw new LocationNotFoundException($id);
        }

        return $dto;
    }

    public function update(string $id, array $data): LocationDTO
    {
        return ($this->update)(new UpdateLocationCommand(
            id: $id,
            name: $data['name'],
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateLocationCommand($id, $actorUserId));
    }

    /**
     * @param  LocationDTO[]  $flat
     * @return LocationDTO[]
     */
    private function buildTree(array $flat): array
    {
        $indexed = [];
        $byParent = [];

        foreach ($flat as $dto) {
            $indexed[$dto->id] = $dto;
            $byParent[$dto->parentId ?? '__root__'][] = $dto;
        }

        foreach ($flat as $dto) {
            if (isset($byParent[$dto->id])) {
                foreach ($byParent[$dto->id] as $child) {
                    $dto->addChild($child);
                }
            }
        }

        return $byParent['__root__'] ?? [];
    }
}
