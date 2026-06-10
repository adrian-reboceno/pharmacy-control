<?php

// ── ARCHIVO: src/PharmaControl/Catalog/Categories/Application/UseCase/GetCategoryTree/GetCategoryTreeUseCase.php ──
declare(strict_types=1);

namespace PharmaControl\Catalog\Categories\Application\UseCase\GetCategoryTree;

use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;

final class GetCategoryTreeUseCase
{
    public function __construct(
        private readonly CategoryRepositoryContract $repository,
    ) {}

    /** @return CategoryDTO[] */
    public function __invoke(GetCategoryTreeQuery $query): array
    {
        $filters = [];
        if ($query->isActive !== null) {
            $filters['is_active'] = $query->isActive;
        }

        $nodes = $this->repository->findAllFlat($filters);

        /** @var array<string, CategoryDTO> $byId */
        $byId = [];
        foreach ($nodes as $node) {
            $byId[$node->getId()->value] = CategoryDTO::fromDomain($node);
        }

        $roots = [];
        foreach ($byId as $dto) {
            if ($dto->parentId === null) {
                $roots[] = $dto;
            } elseif (isset($byId[$dto->parentId])) {
                $byId[$dto->parentId]->addChild($dto);
            }
        }

        return $roots;
    }
}
