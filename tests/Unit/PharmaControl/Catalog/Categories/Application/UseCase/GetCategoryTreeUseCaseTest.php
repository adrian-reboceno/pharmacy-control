<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Application/UseCase/GetCategoryTreeUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Application\UseCase;

use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Application\UseCase\GetCategoryTree\GetCategoryTreeQuery;
use PharmaControl\Catalog\Categories\Application\UseCase\GetCategoryTree\GetCategoryTreeUseCase;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetCategoryTreeUseCaseTest extends TestCase
{
    private CategoryRepositoryContract&MockObject $repository;
    private GetCategoryTreeUseCase                $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CategoryRepositoryContract::class);
        $this->useCase    = new GetCategoryTreeUseCase($this->repository);
    }

    private function makeCategory(?CategoryId $parentId = null, string $slug = 'cat'): Category
    {
        return Category::reconstitute(
            id:          CategoryId::generate(),
            parentId:    $parentId,
            name:        new CategoryName('Cat'),
            slug:        new CategorySlug($slug),
            description: null,
            isActive:    true,
            createdBy:   null,
            createdAt:   new \DateTimeImmutable,
            updatedAt:   new \DateTimeImmutable,
        );
    }

    public function test_returns_empty_array_when_no_categories_exist(): void
    {
        $this->repository->method('findAllFlat')->willReturn([]);

        $result = ($this->useCase)(new GetCategoryTreeQuery);

        self::assertSame([], $result);
    }

    public function test_returns_flat_list_of_root_categories_when_no_children(): void
    {
        $root1 = $this->makeCategory(null, 'medicamentos');
        $root2 = $this->makeCategory(null, 'suplementos');

        $this->repository->method('findAllFlat')->willReturn([$root1, $root2]);

        $result = ($this->useCase)(new GetCategoryTreeQuery);

        self::assertCount(2, $result);
        foreach ($result as $dto) {
            self::assertNull($dto->parentId);
            self::assertEmpty($dto->getChildren());
        }
    }

    public function test_returns_nested_tree_with_children_correctly_assigned(): void
    {
        $parent = $this->makeCategory(null, 'antibioticos');
        $child  = Category::reconstitute(
            id:          CategoryId::generate(),
            parentId:    $parent->getId(),
            name:        new CategoryName('Penicilinas'),
            slug:        new CategorySlug('penicilinas'),
            description: null,
            isActive:    true,
            createdBy:   null,
            createdAt:   new \DateTimeImmutable,
            updatedAt:   new \DateTimeImmutable,
        );

        $this->repository->method('findAllFlat')->willReturn([$parent, $child]);

        $result = ($this->useCase)(new GetCategoryTreeQuery);

        self::assertCount(1, $result);
        self::assertCount(1, $result[0]->getChildren());
        self::assertSame('penicilinas', $result[0]->getChildren()[0]->slug);
    }

    public function test_filters_by_is_active_when_provided(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAllFlat')
            ->with(['is_active' => true])
            ->willReturn([]);

        ($this->useCase)(new GetCategoryTreeQuery(isActive: true));
    }

    public function test_returns_all_nodes_when_is_active_is_null(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAllFlat')
            ->with([])
            ->willReturn([]);

        ($this->useCase)(new GetCategoryTreeQuery(isActive: null));
    }

    public function test_orphaned_nodes_with_missing_parent_do_not_break_tree_build(): void
    {
        $orphan = Category::reconstitute(
            id:          CategoryId::generate(),
            parentId:    CategoryId::generate(),
            name:        new CategoryName('Huerfano'),
            slug:        new CategorySlug('huerfano'),
            description: null,
            isActive:    true,
            createdBy:   null,
            createdAt:   new \DateTimeImmutable,
            updatedAt:   new \DateTimeImmutable,
        );

        $this->repository->method('findAllFlat')->willReturn([$orphan]);

        $result = ($this->useCase)(new GetCategoryTreeQuery);

        self::assertEmpty($result);
    }
}
