<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Products\Domain\ValueObject;

use PharmaControl\Catalog\Products\Domain\ValueObject\ProductMargins;
use Tests\TestCase;

final class ProductMarginsTest extends TestCase
{
    /** @test */
    public function it_accepts_valid_margins(): void
    {
        $margins = new ProductMargins(25.5, 15.0);

        $this->assertSame(25.5, $margins->retailMargin);
        $this->assertSame(15.0, $margins->wholesaleMargin);
    }

    /** @test */
    public function it_accepts_zero_margins(): void
    {
        $margins = new ProductMargins(0.0, 0.0);

        $this->assertSame(0.0, $margins->retailMargin);
    }

    /** @test */
    public function it_accepts_100_percent_margin(): void
    {
        $margins = new ProductMargins(100.0, 100.0);

        $this->assertSame(100.0, $margins->retailMargin);
    }

    /** @test */
    public function it_rejects_negative_retail_margin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProductMargins(-1.0, 15.0);
    }

    /** @test */
    public function it_rejects_retail_margin_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProductMargins(100.1, 15.0);
    }

    /** @test */
    public function it_rejects_negative_wholesale_margin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProductMargins(25.0, -0.1);
    }

    /** @test */
    public function it_rejects_wholesale_margin_above_100(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProductMargins(25.0, 101.0);
    }

    /** @test */
    public function it_calculates_retail_price_correctly(): void
    {
        $margins = new ProductMargins(25.0, 15.0);

        $this->assertSame(125.0, $margins->calculateRetailPrice(100.0));
    }

    /** @test */
    public function it_calculates_wholesale_price_correctly(): void
    {
        $margins = new ProductMargins(25.0, 15.0);

        $this->assertSame(115.0, $margins->calculateWholesalePrice(100.0));
    }

    /** @test */
    public function it_rounds_calculated_price_to_two_decimals(): void
    {
        $margins = new ProductMargins(33.33, 0.0);

        $price = $margins->calculateRetailPrice(10.0);

        $this->assertSame(round(10.0 * 1.3333, 2), $price);
    }
}
