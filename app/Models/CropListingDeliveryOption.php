<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One delivery arrangement a crop listing accepts (F1), e.g. "pickup". Codes: CropListing::DELIVERY_METHODS. */
class CropListingDeliveryOption extends Model
{
    public $timestamps = false;

    protected $fillable = ['method'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CropListing::class, 'crop_listing_id');
    }

    public function label(): string
    {
        return CropListing::deliveryLabels()[$this->method] ?? $this->method;
    }
}
