<?php

namespace Database\Factories;

use App\Models\FarmJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Test and demo farm jobs. The default is an open, daily-wage job next week.
 * Example: FarmJob::factory()->for($farmer, 'farmer')->closed()->create();
 *
 * @extends Factory<FarmJob>
 */
class FarmJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'farmer_id' => User::factory()->withRoles(Role::FARMER),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'work_type' => fake()->randomElement(FarmJob::WORK_TYPES),
            'location' => fake()->city(),
            'work_date' => Carbon::parse(FarmJob::today())->addDays(7),
            'required_workers' => fake()->numberBetween(1, 12),
            'proposed_wage' => fake()->numberBetween(5, 9) * 100,
            'wage_basis' => 'daily',
            'unit_label' => null,
            'meal_details' => null,
            'transport_details' => null,
        ];
    }

    /** The farmer has ended recruitment. status is not fillable, so it is set after the model is built. */
    public function closed(): static
    {
        return $this->afterMaking(function (FarmJob $job) {
            $job->status = FarmJob::STATUS_CLOSED;
        });
    }

    /** The work date was yesterday or earlier; the stored status stays open. */
    public function past(int $daysAgo = 3): static
    {
        return $this->state(['work_date' => Carbon::parse(FarmJob::today())->subDays($daysAgo)]);
    }

    public function taskBased(): static
    {
        return $this->state(['wage_basis' => 'task', 'unit_label' => null]);
    }

    public function unitBased(string $unit = 'bag'): static
    {
        return $this->state(['wage_basis' => 'unit', 'unit_label' => $unit]);
    }
}
