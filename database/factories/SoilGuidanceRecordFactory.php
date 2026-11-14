<?php

namespace Database\Factories;

use App\Exceptions\AiGuidanceException;
use App\Models\Role;
use App\Models\SoilGuidanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Test soil guidance requests (F15). Default: a new, pending request without a photo.
 *
 * Example: SoilGuidanceRecord::factory()->for($farmer, 'farmer')->completed()->create();
 *
 * @extends Factory<SoilGuidanceRecord>
 */
class SoilGuidanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'farmer_id' => User::factory()->withRoles(Role::FARMER),
            'submission_token' => (string) Str::uuid(),
            'crop_name' => fake()->randomElement(['Boro rice', 'Potato', 'Jute', 'Mustard', 'Brinjal']),
            'soil_color' => 'brown',
            'moisture' => 'moist',
            'texture' => 'crumbly',
            'observations' => null,
            'photo_path' => null,
            'locale' => 'en',
            'status' => SoilGuidanceRecord::STATUS_PENDING,
            'attempt_count' => 0,
            'last_attempt_at' => null,
            'error_code' => null,
            'guidance' => null,
            'ai_model' => null,
            'completed_at' => null,
        ];
    }

    /** An answered request with a small, valid guidance result. */
    public function completed(array $guidance = []): static
    {
        return $this->state([
            'status' => SoilGuidanceRecord::STATUS_COMPLETED,
            'attempt_count' => 1,
            'last_attempt_at' => now(),
            'completed_at' => now(),
            'ai_model' => 'test-model',
            'guidance' => array_merge([
                'observations_summary' => 'You grow rice in brown, moist, crumbly soil.',
                'general_soil_care' => ['Add compost or well-rotted cow dung before planting.'],
                'fertilizer_guidance' => ['Use balanced fertilizer in split applications.'],
                'limitations' => ['Only a soil test can show the real nutrient levels.'],
                'follow_up_questions' => ['Does water stand on the field after rain?'],
            ], $guidance),
        ]);
    }

    /** A request whose last attempt failed some time ago (the retry cooldown has passed). */
    public function failed(string $errorCode = AiGuidanceException::TIMEOUT, int $attempts = 1): static
    {
        return $this->state([
            'status' => SoilGuidanceRecord::STATUS_FAILED,
            'attempt_count' => $attempts,
            'last_attempt_at' => now()->subMinutes(10),
            'error_code' => $errorCode,
        ]);
    }

    /** A request that another web request is working on right now. */
    public function processing(): static
    {
        return $this->state([
            'status' => SoilGuidanceRecord::STATUS_PROCESSING,
            'attempt_count' => 1,
            'last_attempt_at' => now(),
        ]);
    }

    /** With a real (tiny) JPEG on the private disk. Call Storage::fake('local') first in tests. */
    public function withPhoto(): static
    {
        return $this->state(['photo_path' => function () {
            $path = SoilGuidanceRecord::PHOTO_DIRECTORY.'/factory-'.Str::random(24).'-display.jpg';
            $image = imagecreatetruecolor(16, 10);
            ob_start();
            imagejpeg($image);
            Storage::disk('local')->put($path, ob_get_clean());

            return $path;
        }]);
    }
}
