<?php

namespace App\Http\Requests\Cattle;

use App\Models\CattleHealthEntry;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

/** "Edit health entry" (route cattle.health-history.update). Corrects one note; the version detects stale tabs. */
class UpdateCattleHealthEntryRequest extends StoreCattleHealthEntryRequest
{
    /**
     * The entry must belong to the animal in the URL: /cattle/1/health-history/99 with entry 99 belonging to
     * cattle 2 is "not found", even when the same farmer owns both animals.
     */
    public function authorize(): bool|Response
    {
        if ($this->entry()->cattle_id !== $this->cattle()->id) {
            return Response::denyAsNotFound();
        }

        return Gate::inspect('update', $this->cattle());
    }

    public function rules(): array
    {
        return $this->entryRules() + ['version' => ['required', 'integer', 'min:1']];
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    public function entry(): CattleHealthEntry
    {
        return $this->route('entry');
    }
}
