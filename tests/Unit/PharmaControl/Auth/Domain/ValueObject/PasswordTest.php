<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Domain/ValueObject/PasswordTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Domain\ValueObject;

use PharmaControl\Auth\Domain\ValueObject\Password;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function test_accepts_valid_complex_password(): void
    {
        $password = new Password('Secret123!');

        self::assertSame('Secret123!', $password->value);
    }

    public function test_rejects_password_shorter_than_8_characters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Password('Ab1!');
    }

    public function test_rejects_password_without_uppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Password('secret123!');
    }

    public function test_rejects_password_without_lowercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Password('SECRET123!');
    }

    public function test_rejects_password_without_number(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Password('SecretPass!');
    }

    public function test_rejects_password_without_special_character(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Password('Secret1234');
    }
}
