<?php

namespace App\Models;

use Database\Factories\MarketPriceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One price observation (F11): a contributor saw this price for one unit of a farm product at a market on a day.
 * Spec: docs/features/F11-market-price-tracking.md.
 *
 * Items cover everything farmers sell: grains, vegetables, fish, meat, poultry, eggs, milk and oil. Each item has a
 * fixed unit (kg, litre or dozen), so unit_price is "taka per kg", "per litre" or "per dozen" depending on the item.
 *
 * History is kept: a new price is a new row, rows are never deleted, and corrections are logged.
 * Only vegetables and fish change during the day, so only their actual prices belong to a session (morning, afternoon,
 * evening). Every other product has one price per day (session "all_day"). An estimate is the contributor's guess for
 * tomorrow (session "all_day", is_estimate = true) and is never shown as an actual price.
 * Writes go through App\Services\MarketPriceService.
 */
class MarketPrice extends Model
{
    /** @use HasFactory<MarketPriceFactory> */
    use HasFactory;

    /**
     * Every item whose price is tracked: code => [group, unit]. Order = the order in the item list.
     * The same codes are enforced by the market_prices_item_check CHECK constraint (add both together).
     */
    public const ITEMS = [
        'rice_coarse' => ['grain', 'kg'], 'rice_medium' => ['grain', 'kg'], 'rice_fine' => ['grain', 'kg'],
        'wheat' => ['grain', 'kg'], 'maize' => ['grain', 'kg'], 'lentil' => ['grain', 'kg'],
        'potato' => ['vegetable', 'kg'], 'onion' => ['vegetable', 'kg'], 'garlic' => ['vegetable', 'kg'],
        'ginger' => ['vegetable', 'kg'], 'green_chilli' => ['vegetable', 'kg'], 'tomato' => ['vegetable', 'kg'],
        'brinjal' => ['vegetable', 'kg'], 'pumpkin' => ['vegetable', 'kg'], 'cucumber' => ['vegetable', 'kg'],
        'cauliflower' => ['vegetable', 'kg'], 'cabbage' => ['vegetable', 'kg'],
        'fish_rui' => ['fish', 'kg'], 'fish_katla' => ['fish', 'kg'], 'fish_pangas' => ['fish', 'kg'],
        'fish_tilapia' => ['fish', 'kg'], 'fish_hilsa' => ['fish', 'kg'], 'fish_prawn' => ['fish', 'kg'],
        'beef' => ['meat', 'kg'], 'mutton' => ['meat', 'kg'],
        'chicken_broiler' => ['poultry', 'kg'], 'chicken_sonali' => ['poultry', 'kg'],
        'chicken_deshi' => ['poultry', 'kg'], 'duck' => ['poultry', 'kg'],
        'egg_chicken' => ['egg', 'dozen'], 'egg_duck' => ['egg', 'dozen'],
        'milk_cow' => ['dairy', 'litre'],
        'oil_mustard' => ['oil', 'litre'], 'oil_soybean' => ['oil', 'litre'],
    ];

    public const GROUPS = ['grain', 'vegetable', 'fish', 'meat', 'poultry', 'egg', 'dairy', 'oil'];

    /** Groups whose price changes during the day, possibly several times. All others have one price per day. */
    public const INTRADAY_GROUPS = ['vegetable', 'fish'];

    /** When during the day a vegetable or fish price applied. */
    public const SESSIONS = ['morning', 'afternoon', 'evening'];

    /** The session for a product with one price per day, and for every estimate. The session CHECK enforces both. */
    public const DAY_SESSION = 'all_day';

    /** What a contributor records: an actual price they saw, or their estimate for tomorrow. */
    public const KINDS = ['actual', 'estimate'];

    public const TYPES = ['retail', 'wholesale'];

    /** Largest price per unit accepted (also a CHECK constraint). */
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
    protected $fillable = ['market_id', 'item_code', 'price_type', 'unit_price', 'price_date', 'price_session', 'is_estimate', 'note'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2', // exact string ("35.50"), never a float
            'price_date' => 'date',
            'is_estimate' => 'boolean',
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

    /** Tomorrow in Bangladesh, as YYYY-MM-DD: the only date an estimate may be for. */
    public static function tomorrow(): string
    {
        return Carbon::parse(self::today())->addDay()->toDateString();
    }

    /** The oldest price date accepted, as YYYY-MM-DD. */
    public static function earliestDate(): string
    {
        return Carbon::parse(self::today())->subDays(self::MAX_AGE_DAYS)->toDateString();
    }

    /** @return list<string> every item code */
    public static function itemCodes(): array
    {
        return array_keys(self::ITEMS);
    }

    /** Does this product's price change during the day (vegetables and fish)? Unknown codes: no. */
    public static function changesDuringDay(?string $itemCode): bool
    {
        return in_array(self::ITEMS[$itemCode ?? ''][0] ?? null, self::INTRADAY_GROUPS, true);
    }

    /** @return array<string, string> code => item name, in the page language */
    public static function itemNames(): array
    {
        return [
            'rice_coarse' => __('Rice (coarse)'),
            'rice_medium' => __('Rice (medium)'),
            'rice_fine' => __('Rice (fine)'),
            'wheat' => __('Wheat'),
            'maize' => __('Maize'),
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
            'cauliflower' => __('Cauliflower'),
            'cabbage' => __('Cabbage'),
            'fish_rui' => __('Rui (fish)'),
            'fish_katla' => __('Katla (fish)'),
            'fish_pangas' => __('Pangas (fish)'),
            'fish_tilapia' => __('Tilapia (fish)'),
            'fish_hilsa' => __('Hilsa (fish)'),
            'fish_prawn' => __('Galda prawn'),
            'beef' => __('Beef'),
            'mutton' => __('Mutton (goat)'),
            'chicken_broiler' => __('Chicken (broiler)'),
            'chicken_sonali' => __('Chicken (sonali)'),
            'chicken_deshi' => __('Chicken (deshi)'),
            'duck' => __('Duck'),
            'egg_chicken' => __('Chicken eggs'),
            'egg_duck' => __('Duck eggs'),
            'milk_cow' => __('Cow milk'),
            'oil_mustard' => __('Mustard oil'),
            'oil_soybean' => __('Soybean oil'),
        ];
    }

    /**
     * code => "Potato (per kg)", for the item select box. The unit is in the name, so a contributor always knows
     * whether to enter the price for a kilogram, a litre or a dozen (this works without JavaScript).
     *
     * @return array<string, string>
     */
    public static function itemOptions(): array
    {
        $names = self::itemNames();

        return collect(self::ITEMS)
            ->mapWithKeys(fn (array $meta, string $code) => [$code => __(':item (per :unit)', ['item' => $names[$code], 'unit' => self::unitName($meta[1])])])
            ->all();
    }

    /** @return array<string, string> group => label */
    public static function groupLabels(): array
    {
        return [
            'grain' => __('Grains and pulses'),
            'vegetable' => __('Vegetables'),
            'fish' => __('Fish'),
            'meat' => __('Meat'),
            'poultry' => __('Poultry'),
            'egg' => __('Eggs'),
            'dairy' => __('Milk'),
            'oil' => __('Oil'),
        ];
    }

    /** "kg", "litre" or "dozen", in the page language. */
    public static function unitName(string $unit): string
    {
        return match ($unit) {
            'kg' => __('kg'),
            'litre' => __('litre'),
            'dozen' => __('dozen'),
            default => $unit,
        };
    }

    /** @return array<string, string> code => label */
    public static function sessionLabels(): array
    {
        return [
            'morning' => __('Morning'),
            'afternoon' => __('Afternoon'),
            'evening' => __('Evening'),
            'all_day' => __('Whole day'),
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

    public function itemName(): string
    {
        return self::itemNames()[$this->item_code] ?? $this->item_code;
    }

    /** The unit this row's price is for: kg, litre or dozen. */
    public function unit(): string
    {
        return self::ITEMS[$this->item_code][1] ?? 'kg';
    }

    public function unitLabel(): string
    {
        return self::unitName($this->unit());
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->price_type] ?? $this->price_type;
    }

    public function sessionLabel(): string
    {
        return self::sessionLabels()[$this->price_session] ?? $this->price_session;
    }

    /** "14 Nov 2026", with Bangla digits on Bangla pages. */
    public function priceDateForDisplay(): string
    {
        return CropListing::localiseDigits($this->price_date->translatedFormat('j M Y'));
    }
}
