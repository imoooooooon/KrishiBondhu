<?php

namespace App\Http\Controllers;

use App\Models\Cattle;
use App\Services\CropListingPhotoService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a cattle photo (route cattle.photo). Cattle photos are always private: only the owner gets them,
 * checked on every request. Another farmer gets 404, a guest is sent to log in.
 */
class CattlePhotoController extends Controller
{
    public function show(Cattle $cattle, string $variant, CropListingPhotoService $photos): Response
    {
        Gate::authorize('view', $cattle);

        $bytes = $photos->read($variant === 'thumb' ? $cattle->photo_thumbnail_path : $cattle->photo_path);
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Length' => (string) strlen($bytes),
        ]);
    }
}
