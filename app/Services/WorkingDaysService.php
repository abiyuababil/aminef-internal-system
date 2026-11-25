<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WorkingDaysService
{
    /**
     * Calculate working days between start and end date (inclusive),
     * excluding weekends (Saturday/Sunday) and national holidays.
     */
    public function calculate(Carbon|string $startDate, Carbon|string $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 0.0;
        }

        $holidayDates = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekend()) {
                continue;
            }
            if ($holidayDates->has($date->toDateString())) {
                continue;
            }
            $days++;
        }

        return (float) $days;
    }

    /**
     * Calculate working days for a date+time range.
     * If a single working day has a time span of > 0 and <= 4 hours, counts as 0.5 day.
     */
    public function calculateRange(
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?string $startTime = null,
        ?string $endTime = null
    ): float {
        $days = $this->calculate($startDate, $endDate);

        if ($days === 1.0 && $startTime && $endTime) {
            try {
                $t1 = Carbon::createFromFormat('H:i', substr($startTime, 0, 5));
                $t2 = Carbon::createFromFormat('H:i', substr($endTime, 0, 5));
                $minutes = $t1->diffInMinutes($t2, false);
                if ($minutes > 0 && $minutes <= 240) {
                    return 0.5;
                }
            } catch (\Throwable) {
                // Fallback to full day if time cannot be parsed
            }
        }

        return $days;
    }
}
