<?php

namespace Tests\Feature\Cattle;

use App\Models\Cattle;
use App\Services\CattleAge;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** F6 spec §9.1: calendar-month ages, month ends, leap days, estimates that keep counting, Dhaka "today". */
class CattleAgeTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string, 2: int}> */
    public static function monthCases(): array
    {
        return [
            'same day' => ['2026-01-15', '2026-01-15', 0],
            'one day short of a month' => ['2026-01-15', '2026-02-14', 0],
            'exactly one month' => ['2026-01-15', '2026-02-15', 1],
            '31 Jan to 28 Feb counts as a month' => ['2026-01-31', '2026-02-28', 1],
            '31 Jan to 27 Feb does not' => ['2026-01-31', '2026-02-27', 0],
            '30 Jan to 29 Feb in a leap year' => ['2028-01-30', '2028-02-29', 1],
            'leap day to 28 Feb next year is a year' => ['2028-02-29', '2029-02-28', 12],
            'over years' => ['2023-05-10', '2026-07-09', 37],
            'future start gives 0' => ['2026-12-01', '2026-11-01', 0],
        ];
    }

    #[DataProvider('monthCases')]
    public function test_calendar_months(string $from, string $to, int $months): void
    {
        $this->assertSame($months, CattleAge::monthsBetween($from, $to));
    }

    public function test_labels_for_exact_estimated_and_unknown_ages(): void
    {
        $today = '2026-11-13';
        $exact = fn (string $born) => tap(new Cattle, function (Cattle $c) use ($born) {
            $c->age_basis = 'exact';
            $c->birth_date = $born;
        });

        $this->assertSame('Born today', CattleAge::describe($exact('2026-11-13'), $today));
        $this->assertSame('5 days', CattleAge::describe($exact('2026-11-08'), $today));
        $this->assertSame('7 months', CattleAge::describe($exact('2026-04-01'), $today));
        $this->assertSame('3 years 2 months', CattleAge::describe($exact('2023-09-13'), $today));
        $this->assertSame('1 year', CattleAge::describe($exact('2025-11-13'), $today));

        $estimate = tap(new Cattle, function ($c) {
            $c->age_basis = 'estimated';
            $c->estimated_age_months = 24;
            $c->age_recorded_on = '2025-11-13';
        });
        $this->assertSame('About 3 years', CattleAge::describe($estimate, $today), 'an estimate keeps counting from the day it was made');

        $newborn = tap(new Cattle, function ($c) {
            $c->age_basis = 'estimated';
            $c->estimated_age_months = 0;
            $c->age_recorded_on = '2026-11-01';
        });
        $this->assertSame('About less than 1 month', CattleAge::describe($newborn, $today));

        $unknown = tap(new Cattle, fn ($c) => $c->age_basis = 'unknown');
        $this->assertSame('Age not recorded', CattleAge::describe($unknown, $today));
    }

    public function test_today_is_the_bangladesh_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-13 18:30:00', 'UTC')); // 00:30 on 14 Nov in Dhaka
        $this->assertSame('2026-11-14', CattleAge::today());
        Carbon::setTestNow();
    }

    public function test_bangla_labels_use_bangla_digits(): void
    {
        app()->setLocale('bn');
        $this->assertSame('৩ বছর ২ মাস', CattleAge::yearsAndMonths(38));
    }
}
