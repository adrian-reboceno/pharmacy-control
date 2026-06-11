<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Domain/Contract/Repository/CategoryRepositoryContract.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Domain\Contract\Repository;

use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;

interface CategoryRepositoryContract
{
    public function save(Category $category): void;

    public function findById(CategoryId $id): ?Category;

    public function findBySlug(CategorySlug $slug): ?Category;

    /**
     * Verifica si $ancestorId es ancestro de $categoryId en el árbol.
     * Implementación con CTE recursiva en PostgreSQL.
     */
    public function isAncestor(CategoryId $ancestorId, CategoryId $categoryId): bool;

    /**
     * Devuelve TODOS los nodos del árbol como lista plana.
     *
     * @param  array{is_active?: bool}  $filters
     * @return Category[]
     */
    public function findAllFlat(array $filters = []): array;

    /**
     * @return Category[]
     */
    public function findChildren(CategoryId $parentId): array;
}
