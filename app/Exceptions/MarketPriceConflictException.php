<?php

namespace App\Exceptions;

use App\Models\MarketPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * A market price entry changed after the contributor opened the page (another tab, another phone), so their
 * correction or withdrawal was NOT applied. Thrown by MarketPriceService.
 *
 * Laravel calls render() automatically: a 409 page shows the latest saved entry next to what was sent.
 */
class MarketPriceConflictException extends RuntimeException
{
    /** @param  array<string, mixed>  $submitted  the fields the contributor sent */
    public function __construct(public readonly MarketPrice $latest, public readonly array $submitted = [])
    {
        parent::__construct('The market price entry was changed by another request.');
    }

    public function render(Request $request): Response
    {
        return response()->view('contributor.prices.conflict', [
            'price' => $this->latest->load('market'),
            'submitted' => $this->submitted,
        ], 409);
    }
}
