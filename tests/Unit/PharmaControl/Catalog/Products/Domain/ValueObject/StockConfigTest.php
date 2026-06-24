<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Products\Domain\ValueObject;

use PharmaControl\Catalog\Products\Domain\ValueObject\StockConfig;
use Tests\TestCase;

final class StockConfigTest extends TestCase
{
    /** @test */
    public function it_accepts_valid_stock_config(): void
    {
        $config = new StockConfig(10, 200, 30, true, false);

        $this->assertSame(10, $config->minStock);
        $this->assertSame(200, $config->maxStock);
        $this->assertSame(30, $config->expiryAlertDays);
        $this->assertTrue($config->manageLots);
        $this->assertFalse($config->allowFraction);
    }

    /** @test */
    public function it_accepts_zero_min_stock(): void
    {
        $config = new StockConfig(0, 1, 1, false, false);

        $this->assertSame(0, $config->minStock);
    }

    /** @test */
    public function it_rejects_negative_min_stock(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StockConfig(-1, 100, 30, true, false);
    }

    /** @test */
    public function it_rejects_max_stock_equal_to_min_stock(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StockConfig(10, 10, 30, true, false);
    }

    /** @test */
    public function it_rejects_max_stock_less_than_min_stock(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StockConfig(50, 10, 30, true, false);
    }

    /** @test */
    public function it_rejects_zero_expiry_alert_days(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StockConfig(0, 100, 0, true, false);
    }

    /** @test */
    public function it_rejects_negative_expiry_alert_days(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new StockConfig(0, 100, -1, true, false);
    }
}
