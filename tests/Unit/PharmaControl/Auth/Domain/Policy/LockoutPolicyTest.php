<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Auth/Domain/Policy/LockoutPolicyTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Auth\Domain\Policy;

use PharmaControl\Auth\Domain\Policy\LockoutPolicy;
use PHPUnit\Framework\TestCase;

final class LockoutPolicyTest extends TestCase
{
    private LockoutPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new LockoutPolicy;
    }

    public function test_is_threshold_reached_returns_true_at_max_attempts(): void
    {
        self::assertTrue($this->policy->isThresholdReached(LockoutPolicy::MAX_ATTEMPTS));
    }

    public function test_is_threshold_reached_returns_false_below_max(): void
    {
        self::assertFalse($this->policy->isThresholdReached(LockoutPolicy::MAX_ATTEMPTS - 1));
    }

    public function test_calculate_duration_returns_base_on_first_lockout(): void
    {
        self::assertSame(LockoutPolicy::DURATION_MIN, $this->policy->calculateDuration(0));
    }

    public function test_calculate_duration_doubles_for_each_reincidence(): void
    {
        $first = $this->policy->calculateDuration(0);
        $second = $this->policy->calculateDuration(1);
        $third = $this->policy->calculateDuration(2);

        self::assertSame($first * 2, $second);
        self::assertSame($first * 4, $third);
    }

    public function test_calculate_duration_is_capped_at_24_hours(): void
    {
        $duration = $this->policy->calculateDuration(100);
        self::assertLessThanOrEqual(1440, $duration);
    }
}
