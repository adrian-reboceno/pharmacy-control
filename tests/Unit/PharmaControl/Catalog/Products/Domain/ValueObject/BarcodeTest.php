<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Products\Domain\ValueObject;

use PharmaControl\Catalog\Products\Domain\Exception\InvalidBarcodeException;
use PharmaControl\Catalog\Products\Domain\ValueObject\Barcode;
use Tests\TestCase;

final class BarcodeTest extends TestCase
{
    /** @test */
    public function it_accepts_a_valid_ean13_barcode(): void
    {
        // 7501031311309 — dígito verificador correcto
        $barcode = new Barcode('7501031311309');

        $this->assertSame('7501031311309', $barcode->value);
    }

    /** @test */
    public function it_rejects_a_barcode_with_fewer_than_13_digits(): void
    {
        $this->expectException(InvalidBarcodeException::class);

        new Barcode('123456789012');
    }

    /** @test */
    public function it_rejects_a_barcode_with_more_than_13_digits(): void
    {
        $this->expectException(InvalidBarcodeException::class);

        new Barcode('12345678901234');
    }

    /** @test */
    public function it_rejects_a_barcode_containing_non_digits(): void
    {
        $this->expectException(InvalidBarcodeException::class);

        new Barcode('7501031311ABC');
    }

    /** @test */
    public function it_rejects_a_barcode_with_invalid_check_digit(): void
    {
        $this->expectException(InvalidBarcodeException::class);

        // 7501031311300 — último dígito incorrecto (correcto sería 9)
        new Barcode('7501031311300');
    }

    /** @test */
    public function it_correctly_computes_equals(): void
    {
        $a = new Barcode('7501031311309');
        $b = new Barcode('7501031311309');

        $this->assertTrue($a->equals($b));
    }

    /** @test */
    public function it_returns_false_for_different_barcodes(): void
    {
        // Another valid EAN-13: 4006381333931
        $a = new Barcode('7501031311309');
        $b = new Barcode('4006381333931');

        $this->assertFalse($a->equals($b));
    }
}
