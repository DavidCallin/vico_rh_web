<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class DateSpan
{
    public static function inclusiveDays(CarbonInterface $start, CarbonInterface $end): int
    {
        $from = $start->copy()->startOfDay();
        $to = $end->copy()->startOfDay();

        return (int) $from->diffInDays($to) + 1;
    }

    public static function overlapDays(
        CarbonInterface $start,
        CarbonInterface $end,
        CarbonInterface $windowStart,
        CarbonInterface $windowEnd,
    ): int {
        $from = $start->copy()->startOfDay()->max($windowStart->copy()->startOfDay());
        $to = $end->copy()->startOfDay()->min($windowEnd->copy()->startOfDay());

        if ($from->greaterThan($to)) {
            return 0;
        }

        return (int) $from->diffInDays($to) + 1;
    }
}
