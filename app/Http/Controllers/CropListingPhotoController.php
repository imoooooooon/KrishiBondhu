<?php

namespace App\Http\Controllers;

use App\Models\CropListingPhoto;
use App\Services\CropListingPhotoService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends one crop listing photo (route crop-listing-photos.show, F1 spec §14).
 *
 * The files are private, so every request comes through here and the PARENT listing is checked each time:
 * published -> anyone; unpublished -> only its owner; removed -> nobody. That is why a photo link copied
 * while a listing was public stops working after the farmer hides or removes it.
 */
class CropListingPhotoController extends Controller
{
    public function show(CropListingPhoto $photo, string $variant, CropListingPhotoService $photos): Response
    {
        $listing = $photo->listing; // null if the listing was removed (soft-deleted)
        abort_if($listing === null, 404);
        Gate::authorize('viewPhoto', $listing);

        $bytes = $photos->read($photo->pathFor($variant)); // $variant is limited to thumb|display by the route
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',          // every stored variant is a JPEG we encoded ourselves
            'X-Content-Type-Options' => 'nosniff',
            // Not cached by browsers or proxies, so hiding a listing takes effect on the next request.
            'Cache-Control' => 'private, no-store',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }
}
