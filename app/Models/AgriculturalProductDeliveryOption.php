<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One delivery arrangement a seller accepts for a product (F25). Codes: AgriculturalProduct::DELIVERY_METHODS. */
class AgriculturalProductDeliveryOption extends Model
{
    public $timestamps = false;

    protected $fillable = ['method'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(AgriculturalProduct::class, 'agricultural_product_id');
    }

    public function label(): string
    {
        return AgriculturalProduct::deliveryLabels()[$this->method] ?? $this->method;
    }
}
