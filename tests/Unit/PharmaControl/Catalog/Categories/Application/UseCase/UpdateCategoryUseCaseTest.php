<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Application/UseCase/UpdateCategoryUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Application\UseCase\UpdateCategory\UpdateCategoryCommand;
use PharmaControl\Catalog\Categories\Application\UseCase\UpdateCategory\UpdateCategoryUseCase;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryUpdated;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryCycleException;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Exception\DuplicateCategorySlugException;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateCategoryUseCaseTest extends TestCase
{
    private CategoryRepositoryContract&MockObject $repository;

    private EventPublisherContract&MockObject $events;

    private UpdateCategoryUseCase $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CategoryRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);
        $this->useCase = new UpdateCategoryUseCase($this->repository, $this->events);
    }

    private function actorId(): string
    {
        return UserId::generate()->value;
    }

    private function makeCategory(?CategoryId $parentId = null, string $slug = 'antibioticos'): Category
    {
        return Category::create(
            CategoryId::generate(),
            $parentId,
            new CategoryName('Antibióticos'),
            new CategorySlug($slug),
            null,
            null,
        );
    }

    public function test_updates_category_and_returns_dto(): void
    {
        $category = $this->makeCategory();
        $category->releaseEvents();

        $this->repository->method('findById')->willReturn($category);
        $this->repository->method('findBySlug')->willReturn(null);
        $this->repository->method('isAncestor')->willReturn(false);
        $this->repository->expects($this->once())->method('save');

        $dto = ($this->useCase)(new UpdateCategoryCommand(
            id: $category->getId()->value,
            parentId: null,
            name: 'Penicilinas',
            slug: 'penicilinas',
            description: 'Grupo de antibióticos',
            actorUserId: $this->actorId(),
        ));

        self::assertInstanceOf(CategoryDTO::class, $dto);
        self::assertSame('Penicilinas', $dto->name);
        self::assertSame('penicilinas', $dto->slug);
    }

    public function test_throws_category_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);
        ($this->useCase)(new UpdateCategoryCommand(
            id: CategoryId::generate()->value,
            parentId: null,
            name: 'X',
            slug: 'x',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }

    public function test_throws_category_not_found_exception_when_new_parent_not_found(): void
    {
        $category = $this->makeCategory();

        $this->repository->method('findById')
            ->willReturnCallback(fn (CategoryId $id) => $id->equals($category->getId()) ? $category : null);

        $this->expectException(CategoryNotFoundException::class);
        ($this->useCase)(new UpdateCategoryCommand(
            id: $category->getId()->value,
            parentId: CategoryId::generate()->value,
            name: 'X',
            slug: 'x',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }

    public function test_throws_category_cycle_exception_when_new_parent_is_descendant(): void
    {
        $category = $this->makeCategory();
        $child = $this->makeCategory($category->getId(), 'hijo');

        $this->repository->method('findById')
            ->willReturnCallback(fn (CategoryId $id) => match (true) {
                $id->equals($category->getId()) => $category,
                $id->equals($child->getId()) => $child,
                default => null,
            });
        $this->repository->method('isAncestor')->willReturn(true);

        $this->expectException(CategoryCycleException::class);
        ($this->useCase)(new UpdateCategoryCommand(
            id: $category->getId()->value,
            parentId: $child->getId()->value,
            name: 'X',
            slug: 'x',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }

    public function test_throws_category_cycle_exception_when_new_parent_is_self(): void
    {
        $category = $this->makeCategory();

        $this->repository->method('findById')->willReturn($category);

        $this->expectException(CategoryCycleException::class);
        ($this->useCase)(new UpdateCategoryCommand(
            id: $category->getId()->value,
            parentId: $category->getId()->value,
            name: 'X',
            slug: 'x',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }

    public function test_throws_duplicate_category_slug_exception_when_slug_belongs_to_different_category(): void
    {
        $category = $this->makeCategory();
        $other = $this->makeCategory(null, 'penicilinas');

        $this->repository->method('findById')->willReturn($category);
        $this->repository->method('isAncestor')->willReturn(false);
        $this->repository->method('findBySlug')->willReturn($other);

        $this->expectException(DuplicateCategorySlugException::class);
        ($this->useCase)(new UpdateCategoryCommand(
            id: $category->getId()->value,
            parentId: null,
            name: 'Penicilinas',
            slug: 'penicilinas',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }

    public function test_allows_keeping_same_slug_on_own_category(): void
    {
        $category = $this->makeCategory(null, 'antibioticos');
        $category->releaseEvents();

        $this->repository->method('findById')->willReturn($category);
        $this->repository->method('isAncestor')->willReturn(false);
        $this->repository->method('findBySlug')->willReturn($category);
        $this->repository->expects($this->once())->method('save');

        $dto = ($this->useCase)(new UpdateCategoryCommand(
            id: $category->getId()->value,
            parentId: null,
            name: 'Antibióticos',
            slug: 'antibioticos',
            description: null,
            actorUserId: $this->actorId(),
        ));

        self::assertSame('antibioticos', $dto->slug);
    }

    public function test_publishes_category_updated_event(): void
    {
        $category = $this->makeCategory();
        $category->releaseEvents();

        $this->repository->method('findById')->willReturn($category);
        $this->repository->method('findBySlug')->willReturn(null);
        $this->repository->method('isAncestor')->willReturn(false);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(CategoryUpdated::class));

        ($this->useCase)(new UpdateCategoryCommand(
            id: $category->getId()->value,
            parentId: null,
            name: 'AINEs',
            slug: 'aines',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }
}
