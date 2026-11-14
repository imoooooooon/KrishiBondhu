<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A veterinarian's public profile (F18). One per user. Farmers only see profiles that are listed.
 */
class VeterinarianProfile extends Model
{
    protected $fillable = [
        'district', 'upazila', 'address',
        'treats_cattle', 'treats_goats', 'treats_poultry',
        'experience_years', 'bio', 'is_listed',
    ];

    protected function casts(): array
    {
        return [
            'treats_cattle' => 'boolean',
            'treats_goats' => 'boolean',
            'treats_poultry' => 'boolean',
            'is_listed' => 'boolean',
            'experience_years' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Only profiles the veterinarian has chosen to show to farmers. */
    public function scopeListed(Builder $query): void
    {
        $query->where('is_listed', true);
    }
}
