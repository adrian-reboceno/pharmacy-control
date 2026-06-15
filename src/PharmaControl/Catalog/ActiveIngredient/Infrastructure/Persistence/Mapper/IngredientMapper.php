<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\ActiveIngredient\Domain\Model\ActiveIngredient;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\CasNumber;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\DciCode;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientId;
use PharmaControl\Catalog\ActiveIngredient\Domain\ValueObject\IngredientName;
use PharmaControl\Catalog\ActiveIngredient\Infrastructure\Persistence\Eloquent\Model\EloquentIngredient;

final class IngredientMapper
{
    public function toDomain(EloquentIngredient $model): ActiveIngredient
    {
        return ActiveIngredient::reconstitute(
            id: new IngredientId($model->id),
            name: new IngredientName($model->name),
            dciCode: new DciCode($model->dci_code),
            casNumber: $model->cas_number !== null ? new CasNumber($model->cas_number) : null,
            description: $model->description,
            isActive: $model->is_active,
            createdBy: $model->created_by !== null ? new UserId($model->created_by) : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    public function toPersistence(ActiveIngredient $i): array
    {
        return [
            'id' => $i->getId()->value,
            'name' => $i->getName()->value,
            'dci_code' => $i->getDciCode()->value,
            'cas_number' => $i->getCasNumber()?->value,
            'description' => $i->getDescription(),
            'is_active' => $i->isActive(),
            'created_by' => $i->getCreatedBy()?->value,
        ];
    }
}
