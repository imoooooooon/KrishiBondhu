<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A Krishi Bondhu account. One person can hold several roles (SRS §2.3).
 * Signs in with a Bangladeshi mobile number; see App\Services\PhoneNumber.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'phone', 'email', 'password', 'locale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /** Farm jobs this user posted as a farmer (F20). */
    public function farmJobs(): HasMany
    {
        return $this->hasMany(FarmJob::class, 'farmer_id');
    }

    /** Crop listings this user published as a farmer (F1). Removed listings are excluded. */
    public function cropListings(): HasMany
    {
        return $this->hasMany(CropListing::class, 'farmer_id');
    }

    /** Cattle in this farmer's private herd (F6), active and archived. */
    public function cattle(): HasMany
    {
        return $this->hasMany(Cattle::class, 'farmer_id');
    }

    /** Does this user hold the role? Pass a Role constant, e.g. Role::FARMER. */
    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }
}
