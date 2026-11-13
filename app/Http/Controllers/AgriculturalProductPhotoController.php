<?php

namespace App\Http\Controllers;

use App\Models\AgriculturalProduct;
use App\Services\CropListingPhotoService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * F25: sends a product photo from the private disk (route shop.photo). The files are never in public/,
 * so this check runs on every request: public while the product is published, always for its own seller,
 * and nobody once it is removed (AgriculturalProductPolicy::viewPhoto).
 */
class AgriculturalProductPhotoController extends Controller
{
    public function show(AgriculturalProduct $product, string $variant, CropListingPhotoService $photos): Response
    {
        Gate::authorize('viewPhoto', $product);
        abort_unless($product->hasPhoto(), 404);

        // $variant is limited to thumb|display by the route.
        $bytes = $photos->read($variant === 'thumb' ? $product->photo_thumbnail_path : $product->photo_path);
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',          // every stored variant is a JPEG we encoded ourselves
            'X-Content-Type-Options' => 'nosniff',
            // Not cached by browsers or proxies, so hiding a product takes effect on the next request.
            'Cache-Control' => 'private, no-store',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }
}
