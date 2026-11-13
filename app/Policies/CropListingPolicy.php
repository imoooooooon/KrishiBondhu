<?php

namespace App\Policies;

use App\Models\CropListing;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may do what with a crop listing (F1). Laravel finds this class by name (CropListing -> CropListingPolicy).
 * Controllers and Form Requests ask it on every request; hiding a button is never the only check.
 *
 * - Farmer workspace (list, create): the Farmer role, otherwise 403.
 * - One listing in the workspace (view, edit, publish, remove): its owner. Another farmer gets 404,
 *   so the response does not even confirm that the listing exists.
 * - Public pages and photos: anyone, but only while the listing is published.
 * - Administrators get no automatic bypass here; administration has its own routes.
 */
class CropListingPolicy
{
    /** "My crop listings" and "Add crop listing". */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::FARMER);
    }

    /** The owner's detail page. */
    public function view(User $user, CropListing $listing): Response
    {
        return $this->ownerOnly($user, $listing);
    }

    /** Edit the content, publish again or unpublish. */
    public function update(User $user, CropListing $listing): Response
    {
        return $this->ownerOnly($user, $listing);
    }

    /** Remove (soft delete). */
    public function delete(User $user, CropListing $listing): Response
    {
        return $this->ownerOnly($user, $listing);
    }

    /** Public marketplace page. $user is null for guests. */
    public function viewPublic(?User $user, CropListing $listing): Response
    {
        return $listing->isPublished() && ! $listing->trashed()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /** A photo: public while the listing is published; the owner can always see their own (not removed) photos. */
    public function viewPhoto(?User $user, CropListing $listing): Response
    {
        if ($listing->trashed()) {
            return Response::denyAsNotFound();
        }

        return $listing->isPublished() || $this->owns($user, $listing)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function ownerOnly(User $user, CropListing $listing): Response
    {
        if (! $user->hasRole(Role::FARMER)) {
            return Response::deny(); // 403: this account has no farmer workspace
        }

        return $this->owns($user, $listing) ? Response::allow() : Response::denyAsNotFound();
    }

    private function owns(?User $user, CropListing $listing): bool
    {
        return $user !== null && $user->id === $listing->farmer_id;
    }
}
