<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Location\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Location\Application\DTO\LocationDTO;
use PharmaControl\Catalog\Location\Domain\Model\Location;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationId;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationLevel;
use PharmaControl\Catalog\Location\Domain\ValueObject\LocationName;
use PharmaControl\Catalog\Location\Infrastructure\Persistence\Eloquent\Model\EloquentLocation;

final class LocationMapper
{
    public function toDomain(EloquentLocation $model): Location
    {
        return Location::reconstitute(
            id: new LocationId($model->id),
            level: LocationLevel::from($model->level),
            parentId: $model->parent_id !== null ? new LocationId($model->parent_id) : null,
            name: new LocationName($model->name),
            description: $model->description,
            isActive: $model->is_active,
            createdBy: $model->created_by !== null ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toDTO(EloquentLocation $model): LocationDTO
    {
        $fullPath = $this->buildFullPath($model);
        $level = LocationLevel::from($model->level);

        return new LocationDTO(
            id: $model->id,
            name: $model->name,
            level: $model->level,
            levelLabel: $level->label(),
            parentId: $model->parent_id,
            description: $model->description,
            isActive: $model->is_active,
            isLeaf: $level->isLeaf(),
            fullPath: $fullPath,
            createdBy: $model->created_by,
            createdAt: $model->created_at->format(\DateTimeInterface::ATOM),
            updatedAt: $model->updated_at->format(\DateTimeInterface::ATOM),
        );
    }

    public function toPersistence(Location $l): array
    {
        return [
            'id' => $l->getId()->value,
            'name' => $l->getName()->value,
            'level' => $l->getLevel()->value,
            'parent_id' => $l->getParentId()?->value,
            'description' => $l->getDescription(),
            'is_active' => $l->isActive(),
            'created_by' => $l->getCreatedBy()?->value,
        ];
    }

    private function buildFullPath(EloquentLocation $model): string
    {
        $parts = [$model->name];
        $current = $model;

        while ($current->parent_id !== null) {
            $current = EloquentLocation::find($current->parent_id);
            if ($current === null) {
                break;
            }
            array_unshift($parts, $current->name);
        }

        return implode(' > ', $parts);
    }
}
