<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Domain/ValueObject/EmailTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Domain\ValueObject;

use PharmaControl\Auth\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_accepts_valid_email_and_normalizes_to_lowercase(): void
    {
        $email = new Email('User@Example.COM');

        self::assertSame('user@example.com', $email->value);
    }

    public function test_rejects_invalid_email_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('not-an-email');
    }

    public function test_rejects_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Email('');
    }

    public function test_equals_compares_case_insensitively(): void
    {
        $a = new Email('Alice@Example.com');
        $b = new Email('alice@example.com');

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_emails(): void
    {
        $a = new Email('alice@example.com');
        $b = new Email('bob@example.com');

        self::assertFalse($a->equals($b));
    }
}
