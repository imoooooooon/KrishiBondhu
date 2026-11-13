<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A user role from SRS §2.3. Rows are created by the roles migration.
 * Use the constants in code, e.g. $user->hasRole(Role::FARMER).
 */
class Role extends Model
{
    public const FARMER = 'farmer';

    public const BUYER = 'buyer';

    public const WORKER = 'worker';

    public const INSEMINATION_AGENT = 'insemination-agent';

    public const PRICE_CONTRIBUTOR = 'price-contributor';

    public const VETERINARIAN = 'veterinarian';

    public const SELLER = 'seller';

    public const ADMIN = 'admin';

    protected $fillable = ['slug', 'name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** Roles people may choose for themselves. Administrator is assigned by the team, never at sign-up. */
    public function scopeSelfAssignable(Builder $query): void
    {
        $query->where('slug', '!=', self::ADMIN);
    }
}
