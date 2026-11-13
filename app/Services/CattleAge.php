<?php

namespace App\Services;

use App\Models\Cattle;
use Illuminate\Support\Carbon;

/**
 * Today's age of an animal, worked out from what the farmer recorded (F6 spec §9.1). Nothing here is stored.
 *
 * - exact:     birth date -> completed years and months; days when under one month; "Born today" on day 0.
 * - estimated: "About …" = the estimate in months + full calendar months since the day it was estimated.
 *              So "about 2 years", recorded a year ago, now reads "about 3 years" without any database change.
 * - unknown:   "Age not recorded". We never invent a birth date.
 *
 * Months are calendar months, not "30 days": 15 Jan -> 15 Feb is one month. If the start day does not exist
 * in the end month, the last day of that month counts (31 Jan -> 28 Feb is one month; 29 Feb -> 28 Feb next
 * year is one year). "Today" is the Bangladesh date.
 */
class CattleAge
{
    public const TIMEZONE = 'Asia/Dhaka';

    public static function today(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
    }

    /** Full calendar months from $from to $to (both YYYY-MM-DD). 0 if $to is not after $from. */
    public static function monthsBetween(string $from, string $to): int
    {
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);

        $months = ($end->year - $start->year) * 12 + ($end->month - $start->month);
        if ($end->day < $start->day && $end->day !== $end->daysInMonth) {
            $months--; // the month is not complete yet
        }

        return max(0, $months);
    }

    /** Current age in months for exact and estimated ages; null when unknown. */
    public static function months(Cattle $cattle, ?string $today = null): ?int
    {
        $today ??= self::today();

        return match ($cattle->age_basis) {
            'exact' => self::monthsBetween($cattle->birth_date->toDateString(), $today),
            'estimated' => $cattle->estimated_age_months + self::monthsBetween($cattle->age_recorded_on->toDateString(), $today),
            default => null,
        };
    }

    /** The label shown on cards and the profile, e.g. "3 years 2 months" or "About 8 months". */
    public static function describe(Cattle $cattle, ?string $today = null): string
    {
        $today ??= self::today();
        $months = self::months($cattle, $today);

        if ($months === null) {
            return __('Age not recorded');
        }

        if ($cattle->age_basis === 'exact' && $months === 0) {
            $days = (int) Carbon::parse($cattle->birth_date->toDateString())->diffInDays(Carbon::parse($today));

            return $days === 0 ? __('Born today') : self::digits(trans_choice(':count day|:count days', $days, ['count' => $days]));
        }

        if ($cattle->age_basis === 'estimated' && $months === 0) {
            return __('About less than 1 month');
        }

        $text = self::yearsAndMonths($months);

        return $cattle->age_basis === 'estimated' ? __('About :age', ['age' => $text]) : $text;
    }

    /** "2 years 3 months", "1 year", "7 months". */
    public static function yearsAndMonths(int $months): string
    {
        $years = intdiv($months, 12);
        $rest = $months % 12;
        $parts = [];
        if ($years > 0) {
            $parts[] = trans_choice(':count year|:count years', $years, ['count' => $years]);
        }
        if ($rest > 0 || $years === 0) {
            $parts[] = trans_choice(':count month|:count months', $rest, ['count' => $rest]);
        }

        return self::digits(implode(' ', $parts));
    }

    private static function digits(string $text): string
    {
        return app()->getLocale() === 'bn'
            ? strtr($text, array_combine(str_split('0123456789'), ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']))
            : $text;
    }
}
