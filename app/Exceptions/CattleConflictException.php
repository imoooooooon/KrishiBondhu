<?php

namespace App\Exceptions;

use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * A cattle profile or health entry changed after the farmer opened the form (another tab or phone), so the
 * save was NOT applied (F6 spec §13). Laravel calls render(): a 409 page shows what the farmer typed and links
 * to the latest form, so nothing they typed is lost and nothing saved is overwritten.
 */
class CattleConflictException extends RuntimeException
{
    /** @param  array<string, mixed>  $submitted  the text fields the farmer sent (never files) */
    public function __construct(
        public readonly Cattle $cattle,
        public readonly array $submitted = [],
        public readonly ?CattleHealthEntry $entry = null,
    ) {
        parent::__construct('The cattle record was changed by another request.');
    }

    public function render(Request $request): Response
    {
        return response()->view('cattle.conflict', [
            'cattle' => $this->cattle,
            'entry' => $this->entry,
            'submitted' => array_filter($this->submitted, fn ($value) => is_scalar($value) && $value !== ''),
        ], 409);
    }
}
