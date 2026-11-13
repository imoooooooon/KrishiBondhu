<?php

namespace App\Exceptions;

use App\Models\AgriculturalProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * A product changed after the seller opened the form (another tab, another phone), so their save,
 * publish or remove was NOT applied. Thrown by AgriculturalProductService.
 *
 * Laravel calls render() automatically: the seller gets a 409 page that shows the latest saved product
 * next to the text they submitted, so a stale tab can never overwrite newer stock or prices.
 */
class AgriculturalProductConflictException extends RuntimeException
{
    /** @param  array<string, mixed>  $submitted  the fields the seller sent */
    public function __construct(public readonly AgriculturalProduct $latest, public readonly array $submitted = [])
    {
        parent::__construct('The agricultural product was changed by another request.');
    }

    public function render(Request $request): Response
    {
        return response()->view('seller.products.conflict', [
            'product' => $this->latest,
            'submitted' => $this->submitted,
        ], 409);
    }
}
