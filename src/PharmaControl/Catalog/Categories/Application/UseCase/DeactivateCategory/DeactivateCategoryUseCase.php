<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/DeactivateCategory/DeactivateCategoryUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\DeactivateCategory;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;

final class DeactivateCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryContract $repository,
        private readonly EventPublisherContract     $events,
    ) {}

    public function __invoke(DeactivateCategoryCommand $cmd): void
    {
        $category = $this->repository->findById(new CategoryId($cmd->id));
        if ($category === null) {
            throw new CategoryNotFoundException($cmd->id);
        }

        $category->deactivate();
        $this->repository->save($category);

        foreach ($category->releaseEvents() as $event) {
            $this->events->publish($event);
        }
    }
}
