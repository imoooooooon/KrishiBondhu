<?php

namespace App\Models;

use App\Services\CattleAge;
use Database\Factories\CattleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One animal in a farmer's private herd (F6). Spec: docs/features/F6-digital-cattle-profiles.md.
 *
 * - public_id is the KB cattle ID (ULID), set once by CattleService. displayId() adds the "KB-C-" prefix.
 * - farm_tag and nickname are the farmer's own labels; they can change, the KB ID never does.
 * - Age: exact birth date, OR an estimate (months + the day it was estimated), OR unknown. See CattleAge.
 * - Archived cattle keep every record; they only leave the active list. Nothing is ever deleted.
 */
class Cattle extends Model
{
    /** @use HasFactory<CattleFactory> */
    use HasFactory;

    /** "cattle" is both singular and plural; say it explicitly so Laravel never looks for "cattles". */
    protected $table = 'cattle';

    public const SEXES = ['female', 'male', 'unknown'];

    public const AGE_BASES = ['exact', 'estimated', 'unknown'];

    public const PER_PAGE = 12;

    public const HISTORY_PER_PAGE = 10;

    /** Folder on the private disk for cattle photos (separate from crop listing photos). */
    public const PHOTO_DIRECTORY = 'cattle-photos';

    /**
     * Only labels the farmer types. Owner, KB ID, age fields, photo paths, version, token and archived_at
     * are set by CattleService, so a form can never choose them.
     */
    protected $fillable = ['nickname', 'farm_tag', 'sex', 'breed_description', 'mother_details', 'father_details'];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'age_recorded_on' => 'date',
            'estimated_age_months' => 'integer',
            'archived_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function healthEntries(): HasMany
    {
        return $this->hasMany(CattleHealthEntry::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** The KB cattle ID as people see it, e.g. KB-C-01JD3X… (always ASCII, so it can be copied and searched). */
    public function displayId(): string
    {
        return 'KB-C-'.$this->public_id;
    }

    /** Page and card title: nickname, else farm tag, else a neutral label. */
    public function title(): string
    {
        return $this->nickname ?: ($this->farm_tag ?: __('Cattle profile'));
    }

    public function ageLabel(): string
    {
        return CattleAge::describe($this);
    }

    public function sexLabel(): string
    {
        return self::sexLabels()[$this->sex] ?? $this->sex;
    }

    /** @return array<string, string> */
    public static function sexLabels(): array
    {
        return ['female' => __('Female'), 'male' => __('Male'), 'unknown' => __('Not recorded')];
    }

    public function photoUrl(string $variant = 'display'): string
    {
        return route('cattle.photo', ['cattle' => $this->id, 'variant' => $variant]);
    }
}
