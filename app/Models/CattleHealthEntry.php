<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One note in an animal's health history (F6): something the farmer recorded about the past, such as a
 * vaccination or a vet visit. It is the farmer's statement, not a verified medical record. Entries are
 * corrected, never deleted, in F6. occurred_on is null when the date is not known; created_at is when the
 * note was typed.
 */
class CattleHealthEntry extends Model
{
    public const TYPES = ['observation', 'vaccination', 'vet_visit', 'treatment_history', 'other'];

    /** cattle_id, recorded_by, version and submission_token are set by CattleHealthHistoryService. */
    protected $fillable = ['entry_type', 'title', 'occurred_on', 'details', 'source_label'];

    protected function casts(): array
    {
        return ['occurred_on' => 'date', 'version' => 'integer'];
    }

    public function cattle(): BelongsTo
    {
        return $this->belongsTo(Cattle::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Changed after it was first saved. */
    public function wasEdited(): bool
    {
        return $this->version > 1;
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->entry_type] ?? $this->entry_type;
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            'observation' => __('Observation'),
            'vaccination' => __('Vaccination'),
            'vet_visit' => __('Vet visit'),
            'treatment_history' => __('Past treatment'),
            'other' => __('Other'),
        ];
    }
}
