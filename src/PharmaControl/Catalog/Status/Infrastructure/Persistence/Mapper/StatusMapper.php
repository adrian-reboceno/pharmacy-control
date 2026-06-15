<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Status\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Status\Domain\Model\ProductStatus;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusCode;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PharmaControl\Catalog\Status\Infrastructure\Persistence\Eloquent\Model\EloquentStatus;

final class StatusMapper
{
    public function toDomain(EloquentStatus $model): ProductStatus
    {
        return ProductStatus::reconstitute(
            id: new StatusId($model->id),
            name: new StatusName($model->name),
            code: new StatusCode($model->code),
            description: $model->description,
            isActive: $model->is_active,
            createdBy: $model->created_by ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toPersistence(ProductStatus $s): array
    {
        return [
            'id' => $s->getId()->value,
            'name' => $s->getName()->value,
            'code' => $s->getCode()->value,
            'description' => $s->getDescription(),
            'is_active' => $s->isActive(),
            'created_by' => $s->getCreatedBy()?->value,
        ];
    }
}
