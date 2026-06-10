<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Laboratories/Domain/ValueObject/WebsiteUrlTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Laboratories\Domain\ValueObject;

use PharmaControl\Catalog\Laboratories\Domain\ValueObject\WebsiteUrl;
use PHPUnit\Framework\TestCase;

final class WebsiteUrlTest extends TestCase
{
    public function test_accepts_valid_https_url(): void
    {
        $url = new WebsiteUrl('https://www.bayer.com');
        self::assertSame('https://www.bayer.com', $url->value);
    }

    public function test_rejects_http_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La URL debe iniciar con https://');

        new WebsiteUrl('http://www.bayer.com');
    }

    public function test_rejects_invalid_url_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL inválida');

        new WebsiteUrl('not-a-url');
    }

    public function test_rejects_url_exceeding_500_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede exceder 500 caracteres');

        new WebsiteUrl('https://example.com/'.str_repeat('a', 490));
    }
}
