<?php

namespace Database\Factories;

use App\Models\Cattle;
use App\Models\Role;
use App\Models\User;
use App\Services\CattleAge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Test cattle (F6). Default: an active female with an exact birth date three years ago, and real (tiny)
 * photo files on the private disk. Call Storage::fake('local') first in tests.
 *
 * Example: Cattle::factory()->for($farmer, 'farmer')->estimated(30)->archived()->create();
 *
 * @extends Factory<Cattle>
 */
class CattleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'farmer_id' => User::factory()->withRoles(Role::FARMER),
            'public_id' => (string) Str::ulid(),
            'nickname' => fake()->randomElement(['Lali', 'Moni', 'Kalo', 'Shundori', 'Raja']),
            'farm_tag' => null,
            'sex' => 'female',
            'breed_description' => 'Red Chittagong',
            'age_basis' => 'exact',
            'birth_date' => Carbon::parse(CattleAge::today())->subYears(3)->toDateString(),
            'estimated_age_months' => null,
            'age_recorded_on' => null,
            'mother_details' => null,
            'father_details' => null,
            'photo_path' => fn () => $this->tinyPhoto('display'),
            'photo_thumbnail_path' => fn () => $this->tinyPhoto('thumb'),
            'photo_width' => 16, 'photo_height' => 10, 'thumbnail_width' => 16, 'thumbnail_height' => 10,
            'version' => 1,
            'submission_token' => (string) Str::uuid(),
            'archived_at' => null,
        ];
    }

    public function estimated(int $months, ?string $recordedOn = null): static
    {
        return $this->state(['age_basis' => 'estimated', 'birth_date' => null, 'estimated_age_months' => $months,
            'age_recorded_on' => $recordedOn ?? CattleAge::today()]);
    }

    public function unknownAge(): static
    {
        return $this->state(['age_basis' => 'unknown', 'birth_date' => null, 'estimated_age_months' => null, 'age_recorded_on' => null]);
    }

    public function archived(): static
    {
        return $this->state(['archived_at' => now()]);
    }

    private function tinyPhoto(string $variant): string
    {
        $path = Cattle::PHOTO_DIRECTORY.'/factory-'.Str::random(24).'-'.$variant.'.jpg';
        $image = imagecreatetruecolor(16, 10);
        ob_start();
        imagejpeg($image);
        Storage::disk('local')->put($path, ob_get_clean());

        return $path;
    }
}
