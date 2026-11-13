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
 * - Public shop pages and the photo: anyone, but only while the product is published (viewPublic, viewPhoto).
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

    /**
     * Public shop page. $user is null for guests. Unpublished, removed and unknown products all give the
     * same 404, so the page never reveals which one it was (the owner previews from the seller workspace).
     */
    public function viewPublic(?User $user, AgriculturalProduct $product): Response
    {
        return $product->isPublished() && ! $product->trashed()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** The photo: public while the product is published; its seller can always see their own (not removed) photo. */
    public function viewPhoto(?User $user, AgriculturalProduct $product): Response
    {
        if ($product->trashed()) {
            return Response::denyAsNotFound();
        }

        return $product->isPublished() || ($user !== null && $user->id === $product->seller_id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function ownerOnly(User $user, AgriculturalProduct $product): Response
    {
        if (! $user->hasRole(Role::SELLER)) {
            return Response::deny(); // 403: this account has no seller workspace
        }

        return $user->id === $product->seller_id ? Response::allow() : Response::denyAsNotFound();
    }
}
