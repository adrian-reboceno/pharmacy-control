<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Domain/Model/CategoryTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryCreated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryDeactivated;
use PharmaControl\Catalog\Categories\Domain\Event\CategoryUpdated;
use PharmaControl\Catalog\Categories\Domain\Model\Category;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryDescription;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    private function makeId(): CategoryId
    {
        return CategoryId::generate();
    }

    private function makeName(string $value = 'Analgésicos'): CategoryName
    {
        return new CategoryName($value);
    }

    private function makeSlug(string $value = 'analgesicos'): CategorySlug
    {
        return new CategorySlug($value);
    }

    private function makeCategory(?CategoryId $parentId = null): Category
    {
        return Category::create(
            $this->makeId(),
            $parentId,
            $this->makeName(),
            $this->makeSlug(),
            null,
            UserId::generate(),
        );
    }

    public function test_create_emits_category_created_event(): void
    {
        $category = $this->makeCategory();
        $events = $category->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(CategoryCreated::class, $events[0]);
    }

    public function test_create_with_null_parent_is_a_root_category(): void
    {
        $category = $this->makeCategory(null);

        self::assertTrue($category->isRoot());
        self::assertNull($category->getParentId());
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $category = Category::reconstitute(
            id: $this->makeId(),
            parentId: null,
            name: $this->makeName(),
            slug: $this->makeSlug(),
            description: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertEmpty($category->releaseEvents());
    }

    public function test_is_root_returns_true_when_parent_id_is_null(): void
    {
        $category = $this->makeCategory(null);

        self::assertTrue($category->isRoot());
    }

    public function test_is_root_returns_false_when_parent_id_is_set(): void
    {
        $category = $this->makeCategory($this->makeId());

        self::assertFalse($category->isRoot());
    }

    public function test_update_changes_name_slug_description_and_emits_category_updated(): void
    {
        $category = $this->makeCategory();
        $category->releaseEvents();

        $newName = new CategoryName('AINEs');
        $newSlug = new CategorySlug('aines');
        $newDesc = new CategoryDescription('Antiinflamatorios no esteroideos');

        $category->update(null, $newName, $newSlug, $newDesc);

        self::assertSame('AINEs', $category->getName()->value);
        self::assertSame('aines', $category->getSlug()->value);
        self::assertSame('Antiinflamatorios no esteroideos', $category->getDescription()?->value);

        $events = $category->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CategoryUpdated::class, $events[0]);
    }

    public function test_deactivate_sets_is_active_false_and_emits_category_deactivated(): void
    {
        $category = $this->makeCategory();
        $category->releaseEvents();

        $category->deactivate();

        self::assertFalse($category->isActive());

        $events = $category->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CategoryDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $category = Category::reconstitute(
            id: $this->makeId(),
            parentId: null,
            name: $this->makeName(),
            slug: $this->makeSlug(),
            description: null,
            isActive: false,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        $this->expectException(\DomainException::class);
        $category->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $category = $this->makeCategory();

        $first = $category->releaseEvents();
        $second = $category->releaseEvents();

        self::assertCount(1, $first);
        self::assertEmpty($second);
    }
}
