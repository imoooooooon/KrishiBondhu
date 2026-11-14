<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TreatmentRecord extends Model
{
    public const CATEGORY_CROP = 'crop';

    public const CATEGORY_ANIMAL = 'animal';

    public const CATEGORIES = [
        self::CATEGORY_CROP,
        self::CATEGORY_ANIMAL,
    ];

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_ONGOING,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'category',
        'subject_name',
        'problem',
        'treatment_notes',
        'treated_on',
        'follow_up_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'treated_on' => 'date',
            'follow_up_date' => 'date',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'farmer_id');
    }

    public function reminder(): MorphOne
    {
        return $this->morphOne(Reminder::class, 'source');
    }
}
