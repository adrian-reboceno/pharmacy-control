<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Infrastructure/Persistence/Eloquent/Repository/EloquentCategoryRepository.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PharmaControl\Catalog\Categories\Infrastructure\Persistence\Eloquent\Model\EloquentCategory;
use PharmaControl\Catalog\Categories\Infrastructure\Persistence\Mapper\CategoryMapper;

final class EloquentCategoryRepository implements CategoryRepositoryContract
{
    public function __construct(private readonly CategoryMapper $mapper) {}

    public function save(Category $category): void
    {
        EloquentCategory::updateOrCreate(
            ['id' => $category->getId()->value],
            $this->mapper->toPersistence($category)
        );
    }

    public function findById(CategoryId $id): ?Category
    {
        $model = EloquentCategory::find($id->value);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function findBySlug(CategorySlug $slug): ?Category
    {
        $model = EloquentCategory::where('slug', $slug->value)->first();

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function isAncestor(CategoryId $ancestorId, CategoryId $categoryId): bool
    {
        $result = DB::select("
            WITH RECURSIVE ancestors AS (
                SELECT id, parent_id
                FROM categories
                WHERE id = :startId

                UNION ALL

                SELECT c.id, c.parent_id
                FROM categories c
                INNER JOIN ancestors a ON c.id = a.parent_id
            )
            SELECT COUNT(*) AS cnt
            FROM ancestors
            WHERE id = :ancestorId
        ", [
            'startId'    => $categoryId->value,
            'ancestorId' => $ancestorId->value,
        ]);

        return ($result[0]->cnt ?? 0) > 0;
    }

    public function findAllFlat(array $filters = []): array
    {
        $query = EloquentCategory::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $query->orderByRaw('parent_id IS NOT NULL, name ASC');

        return array_map(
            fn ($m) => $this->mapper->toDomain($m),
            $query->get()->all()
        );
    }

    public function findChildren(CategoryId $parentId): array
    {
        return array_map(
            fn ($m) => $this->mapper->toDomain($m),
            EloquentCategory::where('parent_id', $parentId->value)
                ->orderBy('name')
                ->get()
                ->all()
        );
    }
}
