<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Categories/Domain/ValueObject/CategoryDescriptionTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Categories\Domain\ValueObject;

use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryDescription;
use PHPUnit\Framework\TestCase;

final class CategoryDescriptionTest extends TestCase
{
    public function test_accepts_valid_description_up_to_500_chars(): void
    {
        $desc = new CategoryDescription(str_repeat('a', 500));

        self::assertSame(str_repeat('a', 500), $desc->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategoryDescription('');
    }

    public function test_rejects_description_over_500_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CategoryDescription(str_repeat('a', 501));
    }
}
