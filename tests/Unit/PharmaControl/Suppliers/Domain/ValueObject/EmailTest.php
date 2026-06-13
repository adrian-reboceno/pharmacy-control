<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Suppliers\Domain\ValueObject;

use PharmaControl\Suppliers\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_accepts_valid_email_and_stores_lowercase(): void
    {
        $email = new Email('Contacto@DFC.mx');
        self::assertSame('contacto@dfc.mx', $email->value);
    }

    public function test_trims_whitespace_before_normalizing(): void
    {
        $email = new Email('  info@pharma.com  ');
        self::assertSame('info@pharma.com', $email->value);
    }

    public function test_already_lowercase_email_is_unchanged(): void
    {
        $email = new Email('admin@example.com');
        self::assertSame('admin@example.com', $email->value);
    }

    public function test_throws_for_email_without_at_sign(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email inválido');

        new Email('notanemail');
    }

    public function test_throws_for_email_without_domain(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('user@');
    }

    public function test_throws_for_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Email('');
    }

    public function test_equals_returns_true_for_same_email_different_case(): void
    {
        $a = new Email('Admin@Example.com');
        $b = new Email('admin@example.com');
        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_email(): void
    {
        $a = new Email('a@example.com');
        $b = new Email('b@example.com');
        self::assertFalse($a->equals($b));
    }
}
