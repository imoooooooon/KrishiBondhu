<?php

namespace App\Models;

use Database\Factories\MarketPriceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One price observation (F11): a contributor saw this price per kilogram for this crop at this market on this day.
 * Spec: docs/features/F11-market-price-tracking.md.
 *
 * History is kept: a new price is a new row, rows are never deleted, and corrections (slice 2) are logged.
 * Writes go through App\Services\MarketPriceService.
 */
class MarketPrice extends Model
{
    /** @use HasFactory<MarketPriceFactory> */
    use HasFactory;

    /** Crops whose prices are tracked, all sold by the kilogram. Also enforced by a CHECK constraint. */
    public const CROPS = [
        'rice_coarse', 'rice_medium', 'rice_fine', 'wheat', 'lentil', 'potato', 'onion',
        'garlic', 'ginger', 'green_chilli', 'tomato', 'brinjal', 'pumpkin', 'cucumber',
    ];

    public const TYPES = ['retail', 'wholesale'];

    /** Largest price per kilogram accepted (also a CHECK constraint). */
    public const MAX_PRICE = '100000';

    /** How far back a price may be dated: one year. Older prices are not "local prices" a buyer can use. */
    public const MAX_AGE_DAYS = 365;

    public const PER_PAGE = 15;

    /** "Today" for the price date is the Bangladesh calendar date. Timestamps stay in UTC. */
    public const TIMEZONE = 'Asia/Dhaka';

    /**
     * What the contributor chooses. contributor_id and version are set by MarketPriceService,
     * so a form can never choose who recorded the price or its version.
     */
    protected $fillable = ['market_id', 'crop_code', 'price_type', 'price_per_kg', 'price_date', 'note'];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2', // exact string ("35.50"), never a float
            'price_date' => 'date',
            'version' => 'integer',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function contributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contributor_id');
    }

    /** The entries one contributor recorded, for "My prices". */
    public function scopeRecordedBy(Builder $query, User $contributor): void
    {
        $query->where('contributor_id', $contributor->id);
    }

    /** Today's date in Bangladesh, as YYYY-MM-DD. */
    public static function today(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
    }

    /** The oldest price date accepted, as YYYY-MM-DD. */
    public static function earliestDate(): string
    {
        return Carbon::parse(self::today())->subDays(self::MAX_AGE_DAYS)->toDateString();
    }

    /** @return array<string, string> code => label, in the page language */
    public static function cropLabels(): array
    {
        return [
            'rice_coarse' => __('Rice (coarse)'),
            'rice_medium' => __('Rice (medium)'),
            'rice_fine' => __('Rice (fine)'),
            'wheat' => __('Wheat'),
            'lentil' => __('Lentil (masur)'),
            'potato' => __('Potato'),
            'onion' => __('Onion'),
            'garlic' => __('Garlic'),
            'ginger' => __('Ginger'),
            'green_chilli' => __('Green chilli'),
            'tomato' => __('Tomato'),
            'brinjal' => __('Brinjal'),
            'pumpkin' => __('Pumpkin'),
            'cucumber' => __('Cucumber'),
        ];
    }

    /** @return array<string, string> code => label */
    public static function typeLabels(): array
    {
        return [
            'retail' => __('Retail'),
            'wholesale' => __('Wholesale'),
        ];
    }

    public function cropLabel(): string
    {
        return self::cropLabels()[$this->crop_code] ?? $this->crop_code;
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->price_type] ?? $this->price_type;
    }

    /** "14 Nov 2026", with Bangla digits on Bangla pages. */
    public function priceDateForDisplay(): string
    {
        return CropListing::localiseDigits($this->price_date->translatedFormat('j M Y'));
    }
}
