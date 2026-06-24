<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Infrastructure\Persistence\Eloquent\Repository;

use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Location\Domain\Model\Location;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;
use PharmaControl\Catalog\Location\Infrastructure\Persistence\Eloquent\Model\EloquentLocation;
use PharmaControl\Catalog\Location\Infrastructure\Persistence\Mapper\LocationMapper;

final class EloquentLocationRepository implements LocationRepositoryContract
{
    public function __construct(private readonly LocationMapper $mapper) {}

    public function save(Location $location): void
    {
        EloquentLocation::updateOrCreate(
            ['id' => $location->getId()->value],
            $this->mapper->toPersistence($location),
        );
    }

    public function findById(LocationId $id): ?Location
    {
        $model = EloquentLocation::find($id->value);

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findByIdAsDTO(LocationId $id): ?LocationDTO
    {
        $model = EloquentLocation::find($id->value);

        return $model !== null ? $this->mapper->toDTO($model) : null;
    }

    public function findByNameAndParent(LocationName $name, ?LocationId $parentId): ?Location
    {
        $query = EloquentLocation::whereRaw('LOWER(name) = ?', [mb_strtolower($name->value)]);

        if ($parentId !== null) {
            $query->where('parent_id', $parentId->value);
        } else {
            $query->whereNull('parent_id');
        }

        $model = $query->first();

        return $model !== null ? $this->mapper->toDomain($model) : null;
    }

    public function findChildren(LocationId $parentId): array
    {
        return array_map(
            fn (EloquentLocation $m) => $this->mapper->toDomain($m),
            EloquentLocation::where('parent_id', $parentId->value)
                ->orderBy('name')
                ->get()
                ->all(),
        );
    }

    public function findAllFlat(array $filters = []): array
    {
        $query = EloquentLocation::query();

        if (isset($filters['level'])) {
            $query->where('level', (int) $filters['level']);
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $query->orderBy('level')->orderBy('name');

        return array_map(
            fn (EloquentLocation $m) => $this->mapper->toDTO($m),
            $query->get()->all(),
        );
    }

    public function findLeaves(array $filters = []): array
    {
        $query = EloquentLocation::where('level', LocationLevel::POSICION->value);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $query->orderBy('name');

        return array_map(
            fn (EloquentLocation $m) => $this->mapper->toDTO($m),
            $query->get()->all(),
        );
    }
}
