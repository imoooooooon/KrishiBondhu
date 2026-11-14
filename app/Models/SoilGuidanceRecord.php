<?php

namespace App\Models;

use App\Exceptions\AiGuidanceException;
use Database\Factories\SoilGuidanceRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One soil guidance request from a farmer and the AI answer to it (F15).
 *
 * The farmer's observations never change after the request is made: a retry sends the same observations,
 * and different observations mean a new request.
 *
 * Status:  pending -> processing -> completed
 *                                -> failed -> processing (retry) -> completed or failed
 *
 * The rules about WHEN a request may be tried again live here (canRetry, retryWaitSeconds, scopeClaimable).
 * WHO may do it is decided by SoilGuidanceRecordPolicy.
 */
class SoilGuidanceRecord extends Model
{
    /** @use HasFactory<SoilGuidanceRecordFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * The choices on the form: saved code => English label. The English label is also what the AI is told,
     * and the key for the Bangla translation in lang/bn.json.
     */
    public const SOIL_COLORS = [
        'brown' => 'Brown', 'dark' => 'Dark or black', 'reddish' => 'Reddish',
        'yellowish' => 'Yellowish', 'other' => 'Other', 'not_sure' => 'Not sure',
    ];

    public const MOISTURES = [
        'dry' => 'Dry', 'moist' => 'Moist', 'waterlogged' => 'Very wet or waterlogged', 'not_sure' => 'Not sure',
    ];

    public const TEXTURES = [
        'sandy' => 'Sandy or gritty', 'sticky' => 'Sticky', 'crumbly' => 'Crumbly',
        'other' => 'Other', 'not_sure' => 'Not sure',
    ];

    public const PHOTO_DIRECTORY = 'soil-guidance-photos';

    public const PER_PAGE = 10;

    /** Dates are shown in Bangladesh time. */
    public const TIMEZONE = 'Asia/Dhaka';

    /**
     * Only what the farmer types or chooses. The owner, status, AI answer and attempt counters are left out
     * on purpose, so a form can never set them.
     */
    protected $fillable = ['crop_name', 'soil_color', 'moisture', 'texture', 'observations'];

    /** A new request starts as pending with no attempts (same as the database defaults). */
    protected $attributes = ['status' => self::STATUS_PENDING, 'attempt_count' => 0];

    protected function casts(): array
    {
        return [
            'guidance' => 'array',
            'attempt_count' => 'integer',
            'last_attempt_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /**
     * Requests that may be sent to the AI service now: never started, failed, or stuck in "processing",
     * and with attempts left. SoilGuidanceService::claim() uses this in ONE update statement, so two
     * requests arriving together cannot both start the same record.
     */
    public function scopeClaimable(Builder $query): void
    {
        $query->where('attempt_count', '<', self::maxAttempts())
            ->where(function (Builder $state) {
                $state->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED])
                    ->orWhere(fn (Builder $stuck) => $stuck
                        ->where('status', self::STATUS_PROCESSING)
                        ->where('last_attempt_at', '<=', self::staleBefore()));
            });
    }

    public static function maxAttempts(): int
    {
        return max(1, (int) config('soil_guidance.max_attempts'));
    }

    /** A "processing" request whose attempt started before this moment has got stuck. */
    public static function staleBefore(): Carbon
    {
        return now()->subSeconds((int) config('soil_guidance.stale_after_seconds'));
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /** Marked "processing" long ago: the attempt never finished (for example the server stopped). */
    public function isStuck(): bool
    {
        return $this->status === self::STATUS_PROCESSING
            && ($this->last_attempt_at === null || $this->last_attempt_at->lte(self::staleBefore()));
    }

    /** An attempt is running right now. The result page asks the farmer to wait. */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_PROCESSING && ! $this->isStuck();
    }

    public function attemptsLeft(): int
    {
        return max(0, self::maxAttempts() - $this->attempt_count);
    }

    /**
     * May the owner press "Try again"? Never for a completed request or one that is being processed now,
     * and never after the attempt limit. Must agree with scopeClaimable() above.
     */
    public function canRetry(): bool
    {
        $waiting = $this->status === self::STATUS_PENDING || $this->isFailed() || $this->isStuck();

        return $waiting && $this->attemptsLeft() > 0;
    }

    /** Seconds the farmer still has to wait before the next try (0 = can try now). */
    public function retryWaitSeconds(): int
    {
        if ($this->last_attempt_at === null) {
            return 0;
        }

        $readyAt = $this->last_attempt_at->copy()->addSeconds((int) config('soil_guidance.retry_cooldown_seconds'));

        return now()->lt($readyAt) ? (int) ceil(now()->diffInSeconds($readyAt, true)) : 0;
    }

    /** @return array<string, string> code => label in the page language */
    public static function soilColorLabels(): array
    {
        return array_map(fn (string $label) => __($label), self::SOIL_COLORS);
    }

    /** @return array<string, string> */
    public static function moistureLabels(): array
    {
        return array_map(fn (string $label) => __($label), self::MOISTURES);
    }

    /** @return array<string, string> */
    public static function textureLabels(): array
    {
        return array_map(fn (string $label) => __($label), self::TEXTURES);
    }

    public function soilColorLabel(): string
    {
        return __(self::SOIL_COLORS[$this->soil_color] ?? 'Not sure');
    }

    public function moistureLabel(): string
    {
        return __(self::MOISTURES[$this->moisture] ?? 'Not sure');
    }

    public function textureLabel(): string
    {
        return __(self::TEXTURES[$this->texture] ?? 'Not sure');
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isCompleted() => __('Ready'),
            $this->isFailed(), $this->isStuck() => __('Failed'),
            $this->isInProgress() => __('In progress'),
            default => __('Waiting'),
        };
    }

    /** Badge colour for the status (design system tones). */
    public function statusTone(): string
    {
        return match (true) {
            $this->isCompleted() => 'success',
            $this->isFailed(), $this->isStuck() => 'danger',
            default => 'info',
        };
    }

    /**
     * A simple message for the farmer about why the last attempt failed. Built from our own safe error code:
     * the provider's raw error text is never shown.
     */
    public function failureMessage(): string
    {
        if ($this->isStuck()) {
            return __('The last attempt did not finish. Your observations have been saved. Please try again.');
        }

        return match ($this->error_code) {
            AiGuidanceException::NOT_CONFIGURED,
            AiGuidanceException::INVALID_CREDENTIALS,
            AiGuidanceException::ACCESS_DENIED,
            AiGuidanceException::MODEL_UNAVAILABLE => __('The AI guidance service is not set up correctly on this website yet. Your observations have been saved. Please try again later.'),
            AiGuidanceException::RATE_LIMITED => __('The AI service is very busy right now. Your observations have been saved. Please try again in a few minutes.'),
            AiGuidanceException::PROVIDER_REJECTED => __('The AI service could not give guidance for this request. Your observations have been saved. You can try again, or make a new request with a clearer description.'),
            AiGuidanceException::INVALID_RESPONSE => __('The AI service gave an answer we could not use. Your observations have been saved. Please try again.'),
            AiGuidanceException::PHOTO_MISSING => __('The saved photo could not be read. Please make a new request and choose the photo again.'),
            default => __('We could not generate guidance right now. Your observations have been saved. Please try again later.'),
        };
    }

    public function hasPhoto(): bool
    {
        return $this->photo_path !== null;
    }

    /** The photo is private: this URL goes through SoilGuidanceController@photo, which checks the owner. */
    public function photoUrl(): string
    {
        return route('guidance.soil.photo', $this);
    }

    /** Request date and time in Bangladesh time, in the page language. */
    public function requestedAtForDisplay(): string
    {
        return $this->created_at->timezone(self::TIMEZONE)->translatedFormat('j M Y, g:i a');
    }

    /**
     * One list from the saved guidance, e.g. guidanceList('limitations'). Always an array of strings,
     * so the result page never breaks on an empty or missing list.
     *
     * @return list<string>
     */
    public function guidanceList(string $key): array
    {
        $items = $this->guidance[$key] ?? [];

        return is_array($items) ? array_values(array_filter($items, 'is_string')) : [];
    }
}
