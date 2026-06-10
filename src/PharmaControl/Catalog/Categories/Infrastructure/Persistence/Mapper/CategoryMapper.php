<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Infrastructure/Persistence/Mapper/CategoryMapper.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Infrastructure\Persistence\Mapper;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryDescription;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PharmaControl\Catalog\Categories\Infrastructure\Persistence\Eloquent\Model\EloquentCategory;

final class CategoryMapper
{
    public function toDomain(EloquentCategory $model): Category
    {
        return Category::reconstitute(
            id:          new CategoryId($model->id),
            parentId:    $model->parent_id ? new CategoryId($model->parent_id) : null,
            name:        new CategoryName($model->name),
            slug:        new CategorySlug($model->slug),
            description: $model->description ? new CategoryDescription($model->description) : null,
            isActive:    $model->is_active,
            createdBy:   $model->created_by ? new UserId($model->created_by) : null,
            createdAt:   $model->created_at,
            updatedAt:   $model->updated_at,
        );
    }

    public function toPersistence(Category $c): array
    {
        return [
            'id'          => $c->getId()->value,
            'parent_id'   => $c->getParentId()?->value,
            'name'        => $c->getName()->value,
            'slug'        => $c->getSlug()->value,
            'description' => $c->getDescription()?->value,
            'is_active'   => $c->isActive(),
            'created_by'  => $c->getCreatedBy()?->value,
        ];
    }
}
