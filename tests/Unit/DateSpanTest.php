<?php

namespace Tests\Unit;

use App\Support\DateSpan;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DateSpanTest extends TestCase
{
    public function test_inclusive_days_count_both_ends(): void
    {
        $days = DateSpan::inclusiveDays(
            CarbonImmutable::parse('2026-04-01'),
            CarbonImmutable::parse('2026-04-03'),
        );

        $this->assertSame(3, $days);
    }

    public function test_overlap_is_limited_to_the_window(): void
    {
        $days = DateSpan::overlapDays(
            CarbonImmutable::parse('2026-03-30'),
            CarbonImmutable::parse('2026-04-02'),
            CarbonImmutable::parse('2026-04-01'),
            CarbonImmutable::parse('2026-04-30'),
        );

        $this->assertSame(2, $days);
    }
}
