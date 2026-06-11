<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Infrastructure/Controller/CategoryController.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Infrastructure\Controller;

use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Application\UseCase\CreateCategory\CreateCategoryCommand;
use PharmaControl\Catalog\Categories\Application\UseCase\CreateCategory\CreateCategoryUseCase;
use PharmaControl\Catalog\Categories\Application\UseCase\DeactivateCategory\DeactivateCategoryCommand;
use PharmaControl\Catalog\Categories\Application\UseCase\DeactivateCategory\DeactivateCategoryUseCase;
use PharmaControl\Catalog\Categories\Application\UseCase\GetCategoryTree\GetCategoryTreeQuery;
use PharmaControl\Catalog\Categories\Application\UseCase\GetCategoryTree\GetCategoryTreeUseCase;
use PharmaControl\Catalog\Categories\Application\UseCase\UpdateCategory\UpdateCategoryCommand;
use PharmaControl\Catalog\Categories\Application\UseCase\UpdateCategory\UpdateCategoryUseCase;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;

final class CategoryController
{
    public function __construct(
        private readonly CreateCategoryUseCase $create,
        private readonly UpdateCategoryUseCase $update,
        private readonly DeactivateCategoryUseCase $deactivate,
        private readonly GetCategoryTreeUseCase $getTree,
        private readonly CategoryRepositoryContract $repository,
    ) {}

    /** @return CategoryDTO[] */
    public function tree(array $data): array
    {
        return ($this->getTree)(new GetCategoryTreeQuery(
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        ));
    }

    public function store(array $data): CategoryDTO
    {
        return ($this->create)(new CreateCategoryCommand(
            parentId: $data['parent_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function show(string $id): CategoryDTO
    {
        $category = $this->repository->findById(new CategoryId($id));
        if ($category === null) {
            throw new CategoryNotFoundException($id);
        }

        return CategoryDTO::fromDomain($category);
    }

    public function update(string $id, array $data): CategoryDTO
    {
        return ($this->update)(new UpdateCategoryCommand(
            id: $id,
            parentId: $data['parent_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            actorUserId: $data['actor_user_id'],
        ));
    }

    public function destroy(string $id, string $actorUserId): void
    {
        ($this->deactivate)(new DeactivateCategoryCommand($id, $actorUserId));
    }
}
