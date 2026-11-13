<?php

namespace App\Services;

use App\Exceptions\CattleConflictException;
use App\Exceptions\UnusableCropPhotoException;
use App\Models\Cattle;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Every write to a cattle profile (F6 spec §13). Same pattern as F1's CropListingService:
 *  1. Photo work happens BEFORE the transaction (using F1's GD pipeline, into the private cattle folder).
 *  2. In the transaction the row is locked (SELECT … FOR UPDATE) and the form's version is compared.
 *  3. On failure the new files of this attempt are deleted; old files are deleted only after commit.
 * Health entries lock the same cattle row first (CattleHealthHistoryService), so a birth-date change and a
 * new health entry can never both pass their date checks against each other.
 */
class CattleService
{
    public function __construct(private readonly CropListingPhotoService $photos) {}

    /**
     * Register an animal. Returns [cattle, created]: created is false when the same form was already sent.
     *
     * @param  array<string, mixed>  $fields  nickname, farm_tag, sex, breed_description, mother_details, father_details
     * @param  array<string, mixed>  $age  ['basis' => exact|estimated|unknown, 'birth_date' => …, 'months' => …]
     * @param  array<string, mixed>|null  $firstEntry  optional first health note
     * @return array{0: Cattle, 1: bool}
     */
    public function register(User $farmer, array $fields, array $age, string $submissionToken, UploadedFile $photo, ?array $firstEntry): array
    {
        if ($existing = $this->findBySubmission($farmer, $submissionToken)) {
            return [$existing, false]; // an archived one stays archived: re-sending never brings it back
        }

        $stored = $this->storePhoto($photo);

        try {
            $cattle = DB::transaction(function () use ($farmer, $fields, $age, $submissionToken, $stored, $firstEntry) {
                $cattle = new Cattle($fields);
                $cattle->farmer_id = $farmer->id;
                $cattle->public_id = (string) Str::ulid(); // the KB ID: random + time based, never from the form
                $cattle->version = 1;
                $cattle->submission_token = $submissionToken;
                $this->applyAge($cattle, $age);
                $this->applyPhoto($cattle, $stored);
                $cattle->save();

                if ($firstEntry) {
                    $entry = $cattle->healthEntries()->make($firstEntry);
                    $entry->recorded_by = $farmer->id;
                    $entry->submission_token = (string) Str::uuid();
                    $entry->save();
                }

                return $cattle;
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->photos->delete($this->pathsOf($stored));
            if ($winner = $this->findBySubmission($farmer, $submissionToken)) {
                return [$winner, false]; // two identical requests raced; the database kept one
            }
            if ($this->tagTaken($farmer, $fields['farm_tag'] ?? null)) {
                throw ValidationException::withMessages(['farm_tag' => __('You already use this farm tag for another animal (also check Archived).')]);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->photos->delete($this->pathsOf($stored));

            throw $e;
        }

        return [$cattle, true];
    }

    /**
     * Save the edit form. $age null = "keep current age information" (nothing about the age changes,
     * including the day an estimate was made). $photo null = keep the current photo.
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>|null  $age
     * @param  array<string, mixed>  $submittedText
     */
    public function update(Cattle $cattle, int $version, array $fields, ?array $age, ?UploadedFile $photo, array $submittedText = []): Cattle
    {
        $stored = $photo ? $this->storePhoto($photo) : null;

        try {
            [$cattle, $oldPaths] = DB::transaction(function () use ($cattle, $version, $fields, $age, $stored, $submittedText) {
                $locked = $this->lockCurrent($cattle, $version, $submittedText);
                $this->assertActive($locked);

                $locked->fill($fields);
                if ($age !== null) {
                    $this->applyAge($locked, $age);
                    $this->assertBirthDateFitsHistory($locked);
                }

                $oldPaths = [];
                if ($stored) {
                    $oldPaths = [$locked->photo_path, $locked->photo_thumbnail_path];
                    $this->applyPhoto($locked, $stored);
                }

                $locked->version = $locked->version + 1;
                $locked->save();

                return [$locked, $oldPaths];
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->photos->delete($this->pathsOf($stored));
            throw ValidationException::withMessages(['farm_tag' => __('You already use this farm tag for another animal (also check Archived).')]);
        } catch (Throwable $e) {
            $this->photos->delete($this->pathsOf($stored));

            throw $e;
        }

        $this->photos->delete($oldPaths); // after commit: a failure here only leaves a private orphan file

        return $cattle;
    }

    /** Move to Archived. Everything is kept; it is not "sold" or "dead". */
    public function archive(Cattle $cattle, int $version): Cattle
    {
        return DB::transaction(function () use ($cattle, $version) {
            $locked = $this->lockCurrent($cattle, $version);
            $this->assertActive($locked);
            $locked->archived_at = now();
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked;
        });
    }

    /** Back to the active list, with the same KB ID and all its history. */
    public function restore(Cattle $cattle, int $version): Cattle
    {
        return DB::transaction(function () use ($cattle, $version) {
            $locked = $this->lockCurrent($cattle, $version);
            if (! $locked->isArchived()) {
                return $locked; // already active: nothing to do
            }
            $locked->archived_at = null;
            $locked->version = $locked->version + 1;
            $locked->save();

            return $locked;
        });
    }

    /** Lock the row until the transaction ends and refuse an out-of-date form (409). */
    public function lockCurrent(Cattle $cattle, ?int $version = null, array $submittedText = []): Cattle
    {
        $locked = Cattle::query()->whereKey($cattle->getKey())->lockForUpdate()->firstOrFail();

        if ($version !== null && $locked->version !== $version) {
            throw new CattleConflictException($locked, $submittedText);
        }

        return $locked;
    }

    public function assertActive(Cattle $cattle): void
    {
        if ($cattle->isArchived()) {
            throw ValidationException::withMessages(['cattle' => __('This animal is archived. Restore it before making changes.')]);
        }
    }

    /** Only the chosen age mode's fields are filled; the others are emptied (also enforced by a CHECK constraint). */
    private function applyAge(Cattle $cattle, array $age): void
    {
        $cattle->age_basis = $age['basis'];
        $cattle->birth_date = $age['basis'] === 'exact' ? $age['birth_date'] : null;
        $cattle->estimated_age_months = $age['basis'] === 'estimated' ? $age['months'] : null;
        // An estimate is "as of today": the server sets the day, the form cannot.
        $cattle->age_recorded_on = $age['basis'] === 'estimated' ? CattleAge::today() : null;
    }

    /** A known birth date cannot be after an event already in the animal's history. */
    private function assertBirthDateFitsHistory(Cattle $cattle): void
    {
        if ($cattle->age_basis !== 'exact') {
            return;
        }

        $earlier = $cattle->healthEntries()->whereNotNull('occurred_on')
            ->whereDate('occurred_on', '<', $cattle->birth_date->toDateString())->count();
        if ($earlier > 0) {
            throw ValidationException::withMessages(['birth_date' => trans_choice(
                'This birth date is after :count health entry. Check the date, or correct that entry first.|This birth date is after :count health entries. Check the date, or correct those entries first.',
                $earlier, ['count' => $earlier],
            )]);
        }
    }

    private function applyPhoto(Cattle $cattle, array $stored): void
    {
        $cattle->photo_path = $stored['display_path'];
        $cattle->photo_thumbnail_path = $stored['thumbnail_path'];
        $cattle->photo_width = $stored['display_width'];
        $cattle->photo_height = $stored['display_height'];
        $cattle->thumbnail_width = $stored['thumbnail_width'];
        $cattle->thumbnail_height = $stored['thumbnail_height'];
    }

    private function storePhoto(UploadedFile $photo): array
    {
        try {
            return $this->photos->store($photo, Cattle::PHOTO_DIRECTORY);
        } catch (UnusableCropPhotoException $e) {
            throw ValidationException::withMessages(['photo' => $e->userMessage()]);
        }
    }

    /** @return list<string> */
    private function pathsOf(?array $stored): array
    {
        return $stored ? [$stored['display_path'], $stored['thumbnail_path']] : [];
    }

    private function findBySubmission(User $farmer, string $token): ?Cattle
    {
        return Cattle::query()->where('farmer_id', $farmer->id)->where('submission_token', $token)->first();
    }

    private function tagTaken(User $farmer, ?string $tag): bool
    {
        return $tag !== null && Cattle::query()->where('farmer_id', $farmer->id)->where('farm_tag', $tag)->exists();
    }
}
