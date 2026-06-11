<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/CreateCategory/CreateCategoryUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\CreateCategory;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Exception\DuplicateCategorySlugException;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryDescription;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;

final class CreateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryContract $repository,
        private readonly EventPublisherContract $events,
    ) {}

    public function __invoke(CreateCategoryCommand $cmd): CategoryDTO
    {
        $parentId = null;
        if ($cmd->parentId !== null) {
            $parentId = new CategoryId($cmd->parentId);
            if ($this->repository->findById($parentId) === null) {
                throw new CategoryNotFoundException($cmd->parentId);
            }
        }

        $name = new CategoryName($cmd->name);
        $description = $cmd->description !== null ? new CategoryDescription($cmd->description) : null;
        $slug = $cmd->slug !== null ? new CategorySlug($cmd->slug) : CategorySlug::generate($name);

        if ($this->repository->findBySlug($slug) !== null) {
            throw new DuplicateCategorySlugException($slug->value);
        }

        $category = Category::create(
            CategoryId::generate(),
            $parentId,
            $name,
            $slug,
            $description,
            new UserId($cmd->actorUserId),
        );

        $this->repository->save($category);

        foreach ($category->releaseEvents() as $event) {
            $this->events->publish($event);
        }

        return CategoryDTO::fromDomain($category);
    }
}
