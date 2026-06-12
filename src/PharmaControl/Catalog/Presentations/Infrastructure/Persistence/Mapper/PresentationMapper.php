<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Presentations\Domain\Model\Presentation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\Abbreviation;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationId;
use PharmaControl\Catalog\Presentations\Domain\ValueObject\PresentationName;
use PharmaControl\Catalog\Presentations\Infrastructure\Persistence\Eloquent\Model\EloquentPresentation;

final class PresentationMapper
{
    public function toDomain(EloquentPresentation $model): Presentation
    {
        return Presentation::reconstitute(
            id:           new PresentationId($model->id),
            name:         new PresentationName($model->name),
            abbreviation: new Abbreviation($model->abbreviation),
            description:  $model->description,
            isActive:     $model->is_active,
            createdBy:    $model->created_by ? new UserId($model->created_by) : null,
            createdAt:    $model->created_at,
            updatedAt:    $model->updated_at,
        );
    }

    public function toPersistence(Presentation $p): array
    {
        return [
            'id'           => $p->getId()->value,
            'name'         => $p->getName()->value,
            'abbreviation' => $p->getAbbreviation()->value,
            'description'  => $p->getDescription(),
            'is_active'    => $p->isActive(),
            'created_by'   => $p->getCreatedBy()?->value,
        ];
    }
}
