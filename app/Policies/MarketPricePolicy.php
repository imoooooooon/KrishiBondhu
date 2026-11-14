<?php

namespace App\Policies;

use App\Models\MarketPrice;
use App\Models\Role;
use App\Models\User;

/**
 * Who may do what with market price entries (F11). Laravel finds this class by name (MarketPrice -> MarketPricePolicy).
 * Controllers and Form Requests ask it on every request; hiding a button is never the only check.
 *
 * - Contributor workspace ("My prices", "Record a price"): the market price contributor role, otherwise 403.
 * - Correcting one entry (slice 2): its own contributor only; another contributor gets 404.
 * - The public price page needs no rule: anyone may read prices.
 * - Administrators get no automatic bypass here.
 * - There is deliberately no delete rule: price history is never deleted.
 */
class MarketPricePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(Role::PRICE_CONTRIBUTOR);
    }
}
