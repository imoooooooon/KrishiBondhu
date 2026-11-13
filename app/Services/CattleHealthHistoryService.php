<?php

namespace App\Services;

use App\Exceptions\CattleConflictException;
use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adds and corrects health-history notes (F6 spec §13). Every write first locks the PARENT cattle row
 * (the same lock CattleService uses), then checks: the animal is not archived, and an event date is not
 * before a known birth date. Lock order is always cattle first, then entry, so two writers cannot deadlock
 * or slip contradictory dates past each other.
 */
class CattleHealthHistoryService
{
    public function __construct(private readonly CattleService $cattleService) {}

    /**
     * @param  array<string, mixed>  $fields
     * @return array{0: CattleHealthEntry, 1: bool} the entry and whether this request created it
     */
    public function create(Cattle $cattle, User $author, array $fields, string $submissionToken): array
    {
        try {
            return DB::transaction(function () use ($cattle, $author, $fields, $submissionToken) {
                $locked = $this->cattleService->lockCurrent($cattle);
                $this->cattleService->assertActive($locked);

                if ($existing = $locked->healthEntries()->where('submission_token', $submissionToken)->first()) {
                    return [$existing, false]; // the same form sent twice
                }

                $this->assertDateFitsBirth($locked, $fields['occurred_on'] ?? null);

                $entry = $locked->healthEntries()->make($fields);
                $entry->recorded_by = $author->id;
                $entry->submission_token = $submissionToken;
                $entry->save();

                return [$entry, true];
            });
        } catch (UniqueConstraintViolationException $e) {
            $winner = $cattle->healthEntries()->where('submission_token', $submissionToken)->first();
            if ($winner) {
                return [$winner, false];
            }

            throw $e;
        }
    }

    /**
     * Correct an entry. The original author is kept; the version goes up by one ("Edited").
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $submittedText
     */
    public function update(Cattle $cattle, CattleHealthEntry $entry, int $version, array $fields, array $submittedText = []): CattleHealthEntry
    {
        return DB::transaction(function () use ($cattle, $entry, $version, $fields, $submittedText) {
            $lockedCattle = $this->cattleService->lockCurrent($cattle);
            $this->cattleService->assertActive($lockedCattle);

            $locked = CattleHealthEntry::query()->where('cattle_id', $lockedCattle->id)->whereKey($entry->getKey())
                ->lockForUpdate()->firstOrFail();
            if ($locked->version !== $version) {
                throw new CattleConflictException($lockedCattle, $submittedText, $locked);
            }

            $this->assertDateFitsBirth($lockedCattle, $fields['occurred_on'] ?? null);

            $locked->fill($fields);
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked;
        });
    }

    private function assertDateFitsBirth(Cattle $cattle, ?string $occurredOn): void
    {
        if ($occurredOn !== null && $cattle->age_basis === 'exact' && $occurredOn < $cattle->birth_date->toDateString()) {
            throw ValidationException::withMessages(['occurred_on' => __('This date is before the animal’s recorded birth date (:date).', [
                'date' => $cattle->birth_date->translatedFormat('j M Y'),
            ])]);
        }
    }
}
