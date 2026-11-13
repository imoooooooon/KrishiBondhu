<?php

namespace App\Exceptions;

use App\Models\CropListing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * A crop listing changed after the farmer opened the form (another tab, another phone), so their
 * save, publish or remove was NOT applied (F1 spec §13.2). Thrown by CropListingService.
 *
 * Laravel calls render() automatically: the farmer gets a 409 page that shows the latest saved
 * listing next to the text they submitted, so they can copy their changes instead of losing them.
 */
class CropListingConflictException extends RuntimeException
{
    /** @param  array<string, mixed>  $submitted  the text fields the farmer sent (never files) */
    public function __construct(public readonly CropListing $latest, public readonly array $submitted = [])
    {
        parent::__construct('The crop listing was changed by another request.');
    }

    public function render(Request $request): Response
    {
        $this->latest->load(['photos', 'deliveryOptions']);

        return response()->view('farmer.listings.conflict', [
            'listing' => $this->latest,
            'submitted' => $this->submitted,
        ], 409);
    }
}
