<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Domain\ValueObject;

use PharmaControl\Suppliers\Domain\ValueObject\Phone;
use PHPUnit\Framework\TestCase;

final class PhoneTest extends TestCase
{
    public function test_accepts_10_digit_string_and_stores_as_is(): void
    {
        $phone = new Phone('2221234567');
        self::assertSame('2221234567', $phone->value);
    }

    public function test_strips_non_digits_and_stores_10_digits(): void
    {
        $phone = new Phone('(222) 123-4567');
        self::assertSame('2221234567', $phone->value);
    }

    public function test_strips_dashes_and_spaces(): void
    {
        $phone = new Phone('222 123 4567');
        self::assertSame('2221234567', $phone->value);
    }

    public function test_throws_when_fewer_than_10_digits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('10 dígitos');

        new Phone('222123456'); // 9 digits
    }

    public function test_throws_when_more_than_10_digits(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Phone('22212345678'); // 11 digits
    }

    public function test_throws_when_no_digits_at_all(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Phone('no-digits-here');
    }

    public function test_equals_returns_true_for_same_digits(): void
    {
        $a = new Phone('2221234567');
        $b = new Phone('(222) 123-4567');
        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_digits(): void
    {
        $a = new Phone('2221234567');
        $b = new Phone('2227654321');
        self::assertFalse($a->equals($b));
    }
}
