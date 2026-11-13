<?php

namespace App\Models;

use Database\Factories\CropListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One crop lot a farmer offers for sale (F1). Spec: docs/features/F1-crop-listings.md.
 *
 * Lifecycle: published <-> unpublished, and either can be removed (soft delete, deleted_at).
 * The writes (create, edit, publish, remove) go through App\Services\CropListingService,
 * which locks the row and checks `version` so two browser tabs cannot overwrite each other.
 */
class CropListing extends Model
{
    /** @use HasFactory<CropListingFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_UNPUBLISHED = 'unpublished';

    /** Trade units. Quantity and price always use the same unit; nothing is ever converted. */
    public const UNITS = ['kg', 'tonne', 'piece'];

    /** Delivery arrangements a farmer can accept. F4 (delivery cost) builds on these codes. */
    public const DELIVERY_METHODS = ['pickup', 'buyer_arranged', 'farmer_arranged', 'third_party'];

    public const MIN_PHOTOS = 1;

    public const MAX_PHOTOS = 5;

    /** Largest quantity and price accepted (also enforced by CHECK constraints in the migration). */
    public const MAX_QUANTITY = '1000000';

    public const MAX_PRICE = '1000000';

    public const PER_PAGE = 12;

    /** "Today" for the harvest date rule is the Bangladesh calendar date. Timestamps stay in UTC. */
    public const TIMEZONE = 'Asia/Dhaka';

    /**
     * Only what the farmer types. farmer_id, status, published_at, version and submission_token are
     * set by CropListingService, so a form can never choose the owner, the status or the version.
     */
    protected $fillable = [
        'crop_name', 'quality_description', 'quantity', 'unit_code', 'price_per_unit',
        'harvest_date', 'public_area', 'delivery_notes',
    ];

    protected function casts(): array
    {
        return [
            // Decimal casts keep exact strings ("35.50"), never binary floating point.
            'quantity' => 'decimal:3',
            'price_per_unit' => 'decimal:2',
            'harvest_date' => 'date',
            'published_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    /** All photos, cover first. */
    public function photos(): HasMany
    {
        return $this->hasMany(CropListingPhoto::class)->orderBy('position');
    }

    /** The cover: the photo with the lowest position. Eager-load it on lists to avoid one query per card. */
    public function coverPhoto(): HasOne
    {
        return $this->hasOne(CropListingPhoto::class)->oldestOfMany('position');
    }

    public function deliveryOptions(): HasMany
    {
        return $this->hasMany(CropListingDeliveryOption::class)->orderBy('id');
    }

    /** Listings anyone can see on the marketplace. Removed ones are already excluded by SoftDeletes. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** Today's date in Bangladesh, as YYYY-MM-DD. */
    public static function today(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
    }

    /** @return array<string, string> code => label, for the unit select box */
    public static function unitLabels(): array
    {
        return [
            'kg' => __('Kilogram (kg)'),
            'tonne' => __('Tonne'),
            'piece' => __('Piece'),
        ];
    }

    /** Short unit name after a number or price: "250 kg", "৳ 35 /kg". */
    public static function unitShortLabel(string $code): string
    {
        return match ($code) {
            'kg' => __('kg'),
            'tonne' => __('tonne'),
            'piece' => __('piece'),
            default => $code,
        };
    }

    /** @return array<string, string> code => label, for the delivery checkboxes and the listing pages */
    public static function deliveryLabels(): array
    {
        return [
            'pickup' => __('Buyer pickup'),
            'buyer_arranged' => __('Buyer arranges transport'),
            'farmer_arranged' => __('Farmer can arrange delivery'),
            'third_party' => __('Third-party delivery'),
        ];
    }

    public function unitLabel(): string
    {
        return self::unitShortLabel($this->unit_code);
    }

    /** @return list<string> the selected delivery codes */
    public function deliveryMethods(): array
    {
        return $this->deliveryOptions->pluck('method')->all();
    }

    /**
     * The quantity for people: "250" instead of "250.000", "12.5" instead of "12.500",
     * with Bangla digits on Bangla pages. Works on the exact decimal string, never a float.
     */
    public function quantityForDisplay(): string
    {
        return self::localiseDigits(self::trimDecimal((string) $this->quantity));
    }

    /** "250.000" -> "250", "12.500" -> "12.5". Input is a plain decimal string from the database. */
    public static function trimDecimal(string $value): string
    {
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }

    public static function localiseDigits(string $value): string
    {
        return app()->getLocale() === 'bn' ? strtr($value, array_combine(str_split('0123456789'), self::BANGLA_DIGITS)) : $value;
    }

    /** ০ to ৯. Also used by the Form Requests to turn Bangla digits typed by a farmer into 0–9. */
    public const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
}
