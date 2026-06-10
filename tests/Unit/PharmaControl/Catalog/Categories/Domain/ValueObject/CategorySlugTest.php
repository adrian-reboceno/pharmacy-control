<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Domain/ValueObject/CategorySlugTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Domain\ValueObject;

use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryName;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategorySlug;
use PHPUnit\Framework\TestCase;

final class CategorySlugTest extends TestCase
{
    public function test_accepts_valid_slug_with_hyphens(): void
    {
        $slug = new CategorySlug('material-de-curacion');

        self::assertSame('material-de-curacion', $slug->value);
    }

    public function test_rejects_slug_with_uppercase_letters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategorySlug('Material-De-Curacion');
    }

    public function test_rejects_slug_with_spaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategorySlug('material de curacion');
    }

    public function test_rejects_slug_with_special_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategorySlug('material@curacion');
    }

    public function test_rejects_slug_exceeding_160_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategorySlug(str_repeat('a', 161));
    }

    public function test_generate_converts_name_with_accents_correctly(): void
    {
        $slug = CategorySlug::generate(new CategoryName('Analgésicos'));

        self::assertSame('analgesicos', $slug->value);
    }

    public function test_generate_converts_spaces_to_hyphens(): void
    {
        $slug = CategorySlug::generate(new CategoryName('Material de Curación'));

        self::assertSame('material-de-curacion', $slug->value);
    }

    public function test_generate_handles_n_correctly(): void
    {
        $slug = CategorySlug::generate(new CategoryName('Vitaminas y Minerales'));

        self::assertSame('vitaminas-y-minerales', $slug->value);
    }

    public function test_generate_handles_enie_correctly(): void
    {
        $slug = CategorySlug::generate(new CategoryName('Año nuevo'));

        self::assertSame('ano-nuevo', $slug->value);
    }

    public function test_generate_trims_leading_and_trailing_hyphens(): void
    {
        $slug = CategorySlug::generate(new CategoryName('  Gasas  '));

        self::assertSame('gasas', $slug->value);
    }
}
