<?php

namespace App\Policies;

use App\Models\MarketPrice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what with market price entries (F11). Laravel finds this class by name (MarketPrice -> MarketPricePolicy).
 * Controllers and Form Requests ask it on every request; hiding a button is never the only check.
 *
 * - Contributor workspace ("My prices", "Record a price"): the market price contributor role, otherwise 403.
 * - Correcting or withdrawing one entry: its own contributor only (another contributor gets 404, so the response does
 *   not even confirm the entry exists), and only within MarketPrice::CORRECTION_DAYS of recording it (later: 403 with
 *   the reason, because older entries are part of the price history).
 * - The public price page needs no rule: anyone may read prices.
 * - Administrators get no automatic bypass here.
 * - Withdrawing never erases the row (soft delete); nothing can hard-delete a price.
 */
class MarketPricePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(Role::PRICE_CONTRIBUTOR);
    }

    /** Correct the price and note. */
    public function update(User $user, MarketPrice $price): Response
    {
        return $this->ownRecentEntry($user, $price);
    }

    /** Withdraw (soft delete with a reason). */
    public function delete(User $user, MarketPrice $price): Response
    {
        return $this->ownRecentEntry($user, $price);
    }

    private function ownRecentEntry(User $user, MarketPrice $price): Response
    {
        if (! $user->hasRole(Role::PRICE_CONTRIBUTOR)) {
            return Response::deny(); // 403: this account has no contributor workspace
        }
        if ($user->id !== $price->contributor_id) {
            return Response::denyAsNotFound();
        }
        if (! $price->canStillChange()) {
            return Response::deny(__('This price was recorded more than :days days ago. It is part of the price history and can no longer be changed. Record a new price instead.', [
                'days' => MarketPrice::CORRECTION_DAYS,
            ]));
        }

        return Response::allow();
    }
}
