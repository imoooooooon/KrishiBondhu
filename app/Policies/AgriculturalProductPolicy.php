<?php

namespace App\Policies;

use App\Models\AgriculturalProduct;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what with a farm-supply product (F25). Laravel finds this class by name
 * (AgriculturalProduct -> AgriculturalProductPolicy). Controllers and Form Requests ask it on every
 * request; hiding a button is never the only check.
 *
 * - Seller workspace (list, create): the Agricultural product seller role, otherwise 403.
 * - One product in the workspace (view, edit, publish, remove): its owner. Another seller gets 404,
 *   so the response does not even confirm that the product exists.
 * - Administrators get no automatic bypass here; administration has its own routes.
 * - The public shop pages (slice 2) add their own viewPublic rule.
 */
class AgriculturalProductPolicy
{
    /** "My products" and "Add product". */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::SELLER);
    }

    /** The owner's detail page, which also shows the private bulk minimum price. */
    public function view(User $user, AgriculturalProduct $product): Response
    {
        return $this->ownerOnly($user, $product);
    }

    /** Edit the content, publish again or unpublish. */
    public function update(User $user, AgriculturalProduct $product): Response
    {
        return $this->ownerOnly($user, $product);
    }

    /** Remove (soft delete). */
    public function delete(User $user, AgriculturalProduct $product): Response
    {
        return $this->ownerOnly($user, $product);
    }

    private function ownerOnly(User $user, AgriculturalProduct $product): Response
    {
        if (! $user->hasRole(Role::SELLER)) {
            return Response::deny(); // 403: this account has no seller workspace
        }

        return $user->id === $product->seller_id ? Response::allow() : Response::denyAsNotFound();
    }
}
