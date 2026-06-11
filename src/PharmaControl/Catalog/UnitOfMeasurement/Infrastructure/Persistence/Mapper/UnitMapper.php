<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\Model\UnitOfMeasurement;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitId;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitName;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitSymbol;
use PharmaControl\Catalog\UnitOfMeasurement\Domain\ValueObject\UnitType;
use PharmaControl\Catalog\UnitOfMeasurement\Infrastructure\Persistence\Eloquent\Model\EloquentUnit;

final class UnitMapper
{
    public function toDomain(EloquentUnit $model): UnitOfMeasurement
    {
        return UnitOfMeasurement::reconstitute(
            id:        new UnitId($model->id),
            name:      new UnitName($model->name),
            symbol:    new UnitSymbol($model->symbol),
            type:      UnitType::from($model->type),
            isActive:  $model->is_active,
            createdBy: $model->created_by !== null ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toPersistence(UnitOfMeasurement $unit): array
    {
        return [
            'id'         => $unit->getId()->value,
            'name'       => $unit->getName()->value,
            'symbol'     => $unit->getSymbol()->value,
            'type'       => $unit->getType()->value,
            'is_active'  => $unit->isActive(),
            'created_by' => $unit->getCreatedBy()?->value,
        ];
    }
}
