<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Application/UseCase/DeactivateCategoryUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Application\UseCase\DeactivateCategory\DeactivateCategoryCommand;
use PharmaControl\Catalog\Categories\Application\UseCase\DeactivateCategory\DeactivateCategoryUseCase;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryDeactivated;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeactivateCategoryUseCaseTest extends TestCase
{
    private CategoryRepositoryContract&MockObject $repository;
    private EventPublisherContract&MockObject     $events;
    private DeactivateCategoryUseCase             $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CategoryRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new DeactivateCategoryUseCase($this->repository, $this->events);
    }

    private function actorId(): string
    {
        return UserId::generate()->value;
    }

    private function makeActiveCategory(): Category
    {
        return Category::create(
            CategoryId::generate(),
            null,
            new CategoryName('Antibióticos'),
            new CategorySlug('antibioticos'),
            null,
            null,
        );
    }

    private function makeInactiveCategory(): Category
    {
        return Category::reconstitute(
            id:          CategoryId::generate(),
            parentId:    null,
            name:        new CategoryName('Antibióticos'),
            slug:        new CategorySlug('antibioticos'),
            description: null,
            isActive:    false,
            createdBy:   null,
            createdAt:   new \DateTimeImmutable,
            updatedAt:   new \DateTimeImmutable,
        );
    }

    public function test_deactivates_active_category(): void
    {
        $category = $this->makeActiveCategory();

        $this->repository->method('findById')->willReturn($category);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)(new DeactivateCategoryCommand($category->getId()->value, $this->actorId()));

        self::assertFalse($category->isActive());
    }

    public function test_throws_category_not_found_exception_when_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);
        ($this->useCase)(new DeactivateCategoryCommand(CategoryId::generate()->value, $this->actorId()));
    }

    public function test_throws_domain_exception_when_already_inactive(): void
    {
        $category = $this->makeInactiveCategory();

        $this->repository->method('findById')->willReturn($category);

        $this->expectException(\DomainException::class);
        ($this->useCase)(new DeactivateCategoryCommand($category->getId()->value, $this->actorId()));
    }

    public function test_does_not_deactivate_children(): void
    {
        $parent = $this->makeActiveCategory();
        $child  = Category::create(
            CategoryId::generate(),
            $parent->getId(),
            new CategoryName('Penicilinas'),
            new CategorySlug('penicilinas'),
            null,
            null,
        );

        $this->repository->method('findById')->willReturn($parent);
        $this->repository->expects($this->once())->method('save');

        ($this->useCase)(new DeactivateCategoryCommand($parent->getId()->value, $this->actorId()));

        self::assertTrue($child->isActive());
    }

    public function test_publishes_category_deactivated_event(): void
    {
        $category = $this->makeActiveCategory();
        $category->releaseEvents();

        $this->repository->method('findById')->willReturn($category);

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(CategoryDeactivated::class));

        ($this->useCase)(new DeactivateCategoryCommand($category->getId()->value, $this->actorId()));
    }
}
