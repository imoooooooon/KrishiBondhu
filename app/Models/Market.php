<?php

namespace App\Models;

use Database\Factories\MarketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A local market (shared reference data for F11 prices and F12 map/distance).
 * The initial markets are inserted by the markets migration; administrators will manage them later (/admin/markets).
 */
class Market extends Model
{
    /** @use HasFactory<MarketFactory> */
    use HasFactory;

    protected $fillable = ['name', 'name_bn', 'upazila', 'district'];

    public function prices(): HasMany
    {
        return $this->hasMany(MarketPrice::class);
    }

    /** The market name in the page language. */
    public function displayName(): string
    {
        return app()->getLocale() === 'bn' ? $this->name_bn : $this->name;
    }

    /** "Kawran Bazar — Tejgaon, Dhaka", for select boxes and tables. */
    public function label(): string
    {
        return $this->displayName().' — '.$this->upazila.', '.$this->district;
    }

    /** @return array<int, string> id => label, ordered by district then name, for select boxes */
    public static function options(): array
    {
        return self::query()->orderBy('district')->orderBy('name')->get()
            ->mapWithKeys(fn (Market $market) => [$market->id => $market->label()])
            ->all();
    }
}
