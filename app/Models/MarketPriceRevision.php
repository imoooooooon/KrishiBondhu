<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One correction of a market price entry (F11): the values it replaced and why. Created only by
 * MarketPriceService::correct(); never updated or deleted, so the price history stays complete.
 */
class MarketPriceRevision extends Model
{
    public const UPDATED_AT = null; // revisions are never changed after they are written

    protected $fillable = ['old_unit_price', 'old_note', 'reason'];

    protected function casts(): array
    {
        return [
            'old_unit_price' => 'decimal:2',
        ];
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(MarketPrice::class, 'market_price_id');
    }

    public function revisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
