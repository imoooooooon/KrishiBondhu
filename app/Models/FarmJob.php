<?php

namespace App\Models;

use Database\Factories\FarmJobFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A farm job posted by a farmer (F20). Workers search the available ones.
 *
 * "Available" means: status is open AND the work date is today or later (Bangladesh date).
 * A job is never deleted; the farmer closes it. F21 (applications, bookings) will reference farm_jobs.id.
 */
class FarmJob extends Model
{
    /** @use HasFactory<FarmJobFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const WORK_TYPES = ['planting', 'harvesting', 'weeding', 'irrigation', 'fertilizer', 'spraying', 'other'];

    public const WAGE_BASES = ['daily', 'task', 'unit'];

    /** Farmers and workers are in Bangladesh, so "today" is the Bangladesh calendar date. */
    public const TIMEZONE = 'Asia/Dhaka';

    /** farmer_id and status are left out on purpose: a form can never set the owner or the status. */
    protected $fillable = [
        'title', 'description', 'work_type', 'location', 'work_date', 'required_workers',
        'proposed_wage', 'wage_basis', 'unit_label', 'meal_details', 'transport_details',
    ];

    /** A new job starts open (same as the database default). */
    protected $attributes = ['status' => self::STATUS_OPEN];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'proposed_wage' => 'decimal:2',
            'required_workers' => 'integer',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /** Today's date in Bangladesh, as YYYY-MM-DD. */
    public static function today(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
    }

    /** Jobs a worker can still take: open, and the work date has not passed. */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', self::STATUS_OPEN)
            ->whereDate('work_date', '>=', self::today());
    }

    /**
     * Apply the search form's filters. Every filter that is filled in must match (AND).
     * The keyword may match the title OR the description, so those two are grouped in brackets.
     *
     * @param  array<string, mixed>  $filters  validated by SearchFarmJobsRequest
     */
    public function scopeSearch(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, function (Builder $query, string $keyword) {
                $like = '%'.self::escapeLike($keyword).'%';
                $query->where(function (Builder $query) use ($like) {
                    $query->where('title', 'like', $like)->orWhere('description', 'like', $like);
                });
            })
            ->when($filters['location'] ?? null, fn (Builder $query, string $location) => $query->where('location', 'like', '%'.self::escapeLike($location).'%'))
            ->when($filters['work_type'] ?? null, fn (Builder $query, string $type) => $query->where('work_type', $type))
            ->when($filters['work_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('work_date', $date))
            ->when($filters['wage_basis'] ?? null, fn (Builder $query, string $basis) => $query->where('wage_basis', $basis))
            ->when($filters['min_wage'] ?? null, fn (Builder $query, $wage) => $query->where('proposed_wage', '>=', $wage));
    }

    /** So that a "%" or "_" typed by the user is searched as a normal character, not as a wildcard. */
    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /** The work date has passed (the stored status may still be "open"). */
    public function isPast(): bool
    {
        return $this->work_date->toDateString() < self::today();
    }

    public function isAvailable(): bool
    {
        return ! $this->isClosed() && ! $this->isPast();
    }

    /** What to show people: open | closed | past. */
    public function displayStatus(): string
    {
        return match (true) {
            $this->isClosed() => 'closed',
            $this->isPast() => 'past',
            default => 'open',
        };
    }

    /** @return array<string, string> value => label, for select boxes */
    public static function workTypeLabels(): array
    {
        return [
            'planting' => __('Planting'),
            'harvesting' => __('Harvesting'),
            'weeding' => __('Weeding'),
            'irrigation' => __('Irrigation'),
            'fertilizer' => __('Fertilizer application'),
            'spraying' => __('Spraying'),
            'other' => __('Other'),
        ];
    }

    /** @return array<string, string> value => label, for select boxes */
    public static function wageBasisLabels(): array
    {
        return [
            'daily' => __('Daily (per day of work)'),
            'task' => __('Task-based (for the whole task)'),
            'unit' => __('Unit-based (per bag, maund, bigha ...)'),
        ];
    }

    public function workTypeLabel(): string
    {
        return self::workTypeLabels()[$this->work_type] ?? $this->work_type;
    }

    /** The words after the amount, e.g. "৳ 700 per worker per day". */
    public function wageLabel(): string
    {
        return match ($this->wage_basis) {
            'daily' => __('per worker per day'),
            'task' => __('per worker for the described task'),
            default => __('per worker per :unit', ['unit' => $this->unit_label]),
        };
    }
}
