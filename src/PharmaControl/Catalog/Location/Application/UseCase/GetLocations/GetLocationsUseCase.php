<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Application\UseCase\GetLocations;

use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;

final class GetLocationsUseCase
{
    public function __construct(
        private readonly LocationRepositoryContract $repository,
    ) {}

    /** @return LocationDTO[] */
    public function __invoke(GetLocationsQuery $query): array
    {
        $filters = [];

        if ($query->level !== null) {
            $filters['level'] = $query->level;
        }
        if ($query->parentId !== null) {
            $filters['parent_id'] = $query->parentId;
        }
        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }

        return $this->repository->findAllFlat($filters);
    }
}
