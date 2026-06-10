<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/UpdateCategory/UpdateCategoryUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\UpdateCategory;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryCycleException;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Exception\DuplicateCategorySlugException;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryDescription;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;

final class UpdateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryContract $repository,
        private readonly EventPublisherContract     $events,
    ) {}

    public function __invoke(UpdateCategoryCommand $cmd): CategoryDTO
    {
        $categoryId = new CategoryId($cmd->id);
        $category   = $this->repository->findById($categoryId);
        if ($category === null) {
            throw new CategoryNotFoundException($cmd->id);
        }

        $parentId = null;
        if ($cmd->parentId !== null) {
            if ($cmd->parentId === $cmd->id) {
                throw new CategoryCycleException($cmd->id, $cmd->parentId);
            }
            $parentId = new CategoryId($cmd->parentId);
            if ($this->repository->findById($parentId) === null) {
                throw new CategoryNotFoundException($cmd->parentId);
            }
            if ($this->repository->isAncestor($categoryId, $parentId)) {
                throw new CategoryCycleException($cmd->id, $cmd->parentId);
            }
        }

        $name        = new CategoryName($cmd->name);
        $description = $cmd->description !== null ? new CategoryDescription($cmd->description) : null;
        $slug        = $cmd->slug !== null ? new CategorySlug($cmd->slug) : CategorySlug::generate($name);

        $existing = $this->repository->findBySlug($slug);
        if ($existing !== null && !$existing->getId()->equals($categoryId)) {
            throw new DuplicateCategorySlugException($slug->value);
        }

        $category->update($parentId, $name, $slug, $description);
        $this->repository->save($category);

        foreach ($category->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return CategoryDTO::fromDomain($category);
    }
}
