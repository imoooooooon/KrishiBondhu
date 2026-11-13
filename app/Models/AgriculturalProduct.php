<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Database\Factories\AgriculturalProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One farm-supply product a seller offers (F25): fertilizer, pesticide, seed, a tool or another supply.
 * Spec: docs/features/F25-agricultural-product-sales.md.
 *
 * Lifecycle: published <-> unpublished, and either can be removed (soft delete, deleted_at), so future
 * orders (F5) and offers (F3) can still point at the row. The writes go through
 * App\Services\AgriculturalProductService, which locks the row and checks `version`.
 *
 * Small purchases use the fixed unit_price. Bulk offers (F3) are only possible when the seller set both
 * bulk fields: from bulk_min_quantity units, at no less than the PRIVATE bulk_min_unit_price.
 */
class AgriculturalProduct extends Model
{
    /** @use HasFactory<AgriculturalProductFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_UNPUBLISHED = 'unpublished';

    public const CATEGORIES = ['fertilizer', 'pesticide', 'seed', 'tool', 'other'];

    /** Sale units. Price and stock always use the same unit; nothing is ever converted. */
    public const UNITS = ['kg', 'litre', 'bag', 'packet', 'bottle', 'piece'];

    /** Delivery arrangements a seller can accept. Same idea as F1's codes; F4 (delivery cost) builds on them. */
    public const DELIVERY_METHODS = ['pickup', 'buyer_arranged', 'seller_arranged', 'third_party'];

    /** Units that are counted, so stock and the bulk threshold must be whole numbers. */
    public const COUNTED_UNITS = ['bag', 'packet', 'bottle', 'piece'];

    /** Largest price and stock accepted (also enforced by CHECK constraints in the migration). */
    public const MAX_PRICE = '1000000';

    public const MAX_STOCK = '1000000';

    /** The smallest bulk threshold: a bulk order is for at least 2 units, so 1 unit is always a fixed-price purchase. */
    public const MIN_BULK_QUANTITY = '2';

    public const PER_PAGE = 12;

    /**
     * Only what the seller types. seller_id, status, published_at, version and submission_token are set by
     * AgriculturalProductService, so a form can never choose the owner, the status or the version.
     */
    protected $fillable = [
        'name', 'brand', 'category', 'description', 'public_area', 'delivery_notes', 'unit_code', 'unit_price',
        'stock_quantity', 'bulk_min_quantity', 'bulk_min_unit_price',
    ];

    /** Folder on the private disk for product photos (files are made by CropListingPhotoService). */
    public const PHOTO_DIRECTORY = 'agricultural-product-photos';

    /**
     * Never in toArray()/toJson(). The public shop pages never print the bulk minimum either;
     * only the owner's pages show it. Photo paths are internal: pages use photoUrl().
     */
    protected $hidden = ['bulk_min_unit_price', 'submission_token', 'photo_path', 'photo_thumbnail_path'];

    protected function casts(): array
    {
        return [
            // Decimal casts keep exact strings ("35.50"), never binary floating point.
            'unit_price' => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'bulk_min_quantity' => 'decimal:3',
            'bulk_min_unit_price' => 'decimal:2',
            'published_at' => 'datetime',
            'version' => 'integer',
            'photo_width' => 'integer',
            'photo_height' => 'integer',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function deliveryOptions(): HasMany
    {
        return $this->hasMany(AgriculturalProductDeliveryOption::class)->orderBy('id');
    }

    /** @return list<string> the selected delivery codes */
    public function deliveryMethods(): array
    {
        return $this->deliveryOptions->pluck('method')->all();
    }

    /** @return array<string, string> code => label, for the delivery checkboxes and the product pages */
    public static function deliveryLabels(): array
    {
        return [
            'pickup' => __('Buyer picks up from the seller'),
            'buyer_arranged' => __('Buyer arranges transport'),
            'seller_arranged' => __('Seller can arrange delivery'),
            'third_party' => __('Third-party delivery (courier)'),
        ];
    }

    /** The products of one seller, for the seller workspace. */
    public function scopeOwnedBy(Builder $query, User $seller): void
    {
        $query->where('seller_id', $seller->id);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * The public shop filters (all optional, already validated by SearchShopRequest). The keyword also matches the area.
     * Prices are compared as exact decimal strings by MySQL, never as PHP floats.
     *
     * @param  array{q?: string, category?: string, min_price?: string, max_price?: string, in_stock?: bool}  $filters
     */
    public function scopeSearch(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, function (Builder $query, string $keyword) {
                $like = '%'.addcslashes($keyword, '\\%_').'%'; // a typed % or _ is a normal character, not a wildcard
                $query->where(function (Builder $query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('public_area', 'like', $like);
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['min_price'] ?? null, fn (Builder $query, string $min) => $query->where('unit_price', '>=', $min))
            ->when($filters['max_price'] ?? null, fn (Builder $query, string $max) => $query->where('unit_price', '<=', $max))
            ->when($filters['in_stock'] ?? false, fn (Builder $query) => $query->where('stock_quantity', '>', 0));
    }

    /**
     * When the product was first listed in the shop, in Bangladesh time: "14 Nov 2026, 4:15 am" (Bangla digits on
     * Bangla pages). The shop is sorted by this time, newest first. Null for a product that was never published.
     */
    public function listedAtForDisplay(): ?string
    {
        return $this->published_at === null
            ? null
            : CropListing::localiseDigits($this->published_at->timezone(CropListing::TIMEZONE)->translatedFormat('j M Y, g:i a'));
    }

    public function hasPhoto(): bool
    {
        return $this->photo_path !== null;
    }

    /** The photo route (visibility is checked there on every request). Null when there is no photo. */
    public function photoUrl(string $variant = 'display'): ?string
    {
        return $this->hasPhoto() ? route('shop.photo', ['product' => $this->id, 'variant' => $variant]) : null;
    }

    /** @return list<string> both stored files, for cleanup */
    public function photoPaths(): array
    {
        return $this->hasPhoto() ? [$this->photo_path, $this->photo_thumbnail_path] : [];
    }

    /** Bulk offers are accepted AND there is enough stock right now for the smallest bulk order. */
    public function bulkAvailableNow(): bool
    {
        return $this->acceptsBulkOffers()
            && BigDecimal::of((string) $this->stock_quantity)->isGreaterThanOrEqualTo((string) $this->bulk_min_quantity);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** Exact decimal comparison (never a float): "0.000" is out of stock. */
    public function isOutOfStock(): bool
    {
        return BigDecimal::of((string) $this->stock_quantity)->isZero();
    }

    public function acceptsBulkOffers(): bool
    {
        return $this->bulk_min_quantity !== null && $this->bulk_min_unit_price !== null;
    }

    /** @return array<string, string> code => label */
    public static function categoryLabels(): array
    {
        return [
            'fertilizer' => __('Fertilizer'),
            'pesticide' => __('Pesticide'),
            'seed' => __('Seed'),
            'tool' => __('Tool or equipment'),
            'other' => __('Other supply'),
        ];
    }

    /** @return array<string, string> code => label, for the unit select box */
    public static function unitLabels(): array
    {
        return [
            'kg' => __('Kilogram (kg)'),
            'litre' => __('Litre'),
            'bag' => __('Bag'),
            'packet' => __('Packet'),
            'bottle' => __('Bottle'),
            'piece' => __('Piece'),
        ];
    }

    /** Short unit name after a number or price: "20 bag", "৳ 1,200 /bag". */
    public static function unitShortLabel(string $code): string
    {
        return match ($code) {
            'kg' => __('kg'),
            'litre' => __('litre'),
            'bag' => __('bag'),
            'packet' => __('packet'),
            'bottle' => __('bottle'),
            'piece' => __('piece'),
            default => $code,
        };
    }

    public function unitLabel(): string
    {
        return self::unitShortLabel($this->unit_code);
    }

    public function categoryLabel(): string
    {
        return self::categoryLabels()[$this->category] ?? $this->category;
    }

    /** "20" instead of "20.000", Bangla digits on Bangla pages. Works on the exact string, never a float. */
    public function stockForDisplay(): string
    {
        return CropListing::localiseDigits(CropListing::trimDecimal((string) $this->stock_quantity));
    }

    public function bulkMinQuantityForDisplay(): ?string
    {
        return $this->bulk_min_quantity === null
            ? null
            : CropListing::localiseDigits(CropListing::trimDecimal((string) $this->bulk_min_quantity));
    }
}
