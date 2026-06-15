<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Status\Domain\ValueObject;

use PharmaControl\Catalog\Status\Domain\ValueObject\StatusName;
use PHPUnit\Framework\TestCase;

final class StatusNameTest extends TestCase
{
    public function test_accepts_valid_name_up_to_50_chars(): void
    {
        $name = new StatusName(str_repeat('A', 50));
        $this->assertSame(str_repeat('A', 50), $name->value);
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StatusName('');
    }

    public function test_rejects_name_over_50_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StatusName(str_repeat('A', 51));
    }

    public function test_trims_whitespace(): void
    {
        $name = new StatusName('  Activo  ');
        $this->assertSame('Activo', $name->value);
    }

    public function test_equals_is_case_insensitive(): void
    {
        $a = new StatusName('Activo');
        $b = new StatusName('ACTIVO');

        $this->assertTrue($a->equals($b));
    }
}
