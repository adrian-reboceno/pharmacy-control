<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PharmaControl\Catalog\RoutesOfAdministration\Infrastructure\Persistence\Eloquent\Model\EloquentRoute;

final class RouteMapper
{
    public function toDomain(EloquentRoute $model): RouteOfAdministration
    {
        return RouteOfAdministration::reconstitute(
            id: new RouteId($model->id),
            name: new RouteName($model->name),
            code: new RouteCode($model->code),
            description: $model->description,
            isActive: $model->is_active,
            createdBy: $model->created_by ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toPersistence(RouteOfAdministration $r): array
    {
        return [
            'id' => $r->getId()->value,
            'name' => $r->getName()->value,
            'code' => $r->getCode()->value,
            'description' => $r->getDescription(),
            'is_active' => $r->isActive(),
            'created_by' => $r->getCreatedBy()?->value,
        ];
    }
}
