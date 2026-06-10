<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Application/UseCase/CreateCategoryUseCaseTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Application\DTO\CategoryDTO;
use PharmaControl\Catalog\Categories\Application\UseCase\CreateCategory\CreateCategoryCommand;
use PharmaControl\Catalog\Categories\Application\UseCase\CreateCategory\CreateCategoryUseCase;
use PharmaControl\Catalog\Categories\Domain\Contract\Repository\CategoryRepositoryContract;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryCreated;
use PharmaControl\Catalog\Categories\Domain\Exception\CategoryNotFoundException;
use PharmaControl\Catalog\Categories\Domain\Exception\DuplicateCategorySlugException;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateCategoryUseCaseTest extends TestCase
{
    private CategoryRepositoryContract&MockObject $repository;
    private EventPublisherContract&MockObject     $events;
    private CreateCategoryUseCase                 $useCase;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CategoryRepositoryContract::class);
        $this->events     = $this->createMock(EventPublisherContract::class);
        $this->useCase    = new CreateCategoryUseCase($this->repository, $this->events);
    }

    private function actorId(): string
    {
        return UserId::generate()->value;
    }

    private function makeCategory(?CategoryId $parentId = null): Category
    {
        return Category::create(
            CategoryId::generate(),
            $parentId,
            new CategoryName('Antibióticos'),
            new CategorySlug('antibioticos'),
            null,
            null,
        );
    }

    public function test_creates_root_category_and_returns_dto(): void
    {
        $this->repository->method('findById')->willReturn(null);
        $this->repository->method('findBySlug')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $dto = ($this->useCase)(new CreateCategoryCommand(
            parentId:    null,
            name:        'Medicamentos',
            slug:        'medicamentos',
            description: null,
            actorUserId: $this->actorId(),
        ));

        self::assertInstanceOf(CategoryDTO::class, $dto);
        self::assertSame('Medicamentos', $dto->name);
        self::assertSame('medicamentos', $dto->slug);
        self::assertTrue($dto->isRoot);
    }

    public function test_creates_child_category_with_valid_parent(): void
    {
        $parent = $this->makeCategory();

        $this->repository->method('findById')->willReturn($parent);
        $this->repository->method('findBySlug')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $dto = ($this->useCase)(new CreateCategoryCommand(
            parentId:    $parent->getId()->value,
            name:        'Penicilinas',
            slug:        'penicilinas',
            description: null,
            actorUserId: $this->actorId(),
        ));

        self::assertFalse($dto->isRoot);
        self::assertSame($parent->getId()->value, $dto->parentId);
    }

    public function test_throws_category_not_found_exception_when_parent_not_found(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);
        ($this->useCase)(new CreateCategoryCommand(
            parentId:    CategoryId::generate()->value,
            name:        'Penicilinas',
            slug:        null,
            description: null,
            actorUserId: $this->actorId(),
        ));
    }

    public function test_throws_duplicate_category_slug_exception_when_slug_already_exists(): void
    {
        $existing = $this->makeCategory();

        $this->repository->method('findById')->willReturn(null);
        $this->repository->method('findBySlug')->willReturn($existing);

        $this->expectException(DuplicateCategorySlugException::class);
        ($this->useCase)(new CreateCategoryCommand(
            parentId:    null,
            name:        'Antibióticos',
            slug:        'antibioticos',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }

    public function test_auto_generates_slug_when_not_provided(): void
    {
        $this->repository->method('findBySlug')->willReturn(null);
        $this->repository->expects($this->once())->method('save');

        $dto = ($this->useCase)(new CreateCategoryCommand(
            parentId:    null,
            name:        'Analgésicos',
            slug:        null,
            description: null,
            actorUserId: $this->actorId(),
        ));

        self::assertSame('analgesicos', $dto->slug);
    }

    public function test_publishes_category_created_event(): void
    {
        $this->repository->method('findBySlug')->willReturn(null);
        $this->repository->method('save');

        $this->events->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(CategoryCreated::class));

        ($this->useCase)(new CreateCategoryCommand(
            parentId:    null,
            name:        'Suplementos',
            slug:        'suplementos',
            description: null,
            actorUserId: $this->actorId(),
        ));
    }
}
