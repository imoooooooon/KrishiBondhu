<?php

namespace Database\Seeders;

use App\Models\FarmJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data for F20, for development and the viva only:
 *     php artisan db:seed --class=FarmJobDemoSeeder
 *
 * Creates three demo accounts (password: "password") and a mix of open, closed and past jobs.
 * Dates are counted from today, so the examples stay useful later.
 * Safe to run twice: existing accounts are never changed, and jobs are added only if the demo farmers have none.
 */
class FarmJobDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('FarmJobDemoSeeder is for development only. Nothing was added.');

            return;
        }

        $karim = $this->demoUser('01700000001', 'Karim Mia (demo farmer)', [Role::FARMER]);
        $salma = $this->demoUser('01700000002', 'Salma Khatun (demo farmer and worker)', [Role::FARMER, Role::WORKER]);
        $this->demoUser('01700000003', 'Rafiq Islam (demo worker)', [Role::WORKER]);

        if ($karim->farmJobs()->exists() || $salma->farmJobs()->exists()) {
            $this->command?->info('Demo farm jobs already exist. Nothing was added.');

            return;
        }

        $today = Carbon::parse(FarmJob::today());

        // [farmer, days from today, status, job fields]
        $jobs = [
            [$karim, 2, 'open', ['title' => 'Harvest Aman rice, 3 bigha', 'work_type' => 'harvesting', 'location' => 'Birulia, Savar, Dhaka',
                'description' => "Cut and bundle Aman rice on 3 bigha of land.\nWork starts at 6 am. Bring your own sickle.",
                'required_workers' => 8, 'proposed_wage' => 700, 'wage_basis' => 'daily',
                'meal_details' => 'Lunch and afternoon tea provided', 'transport_details' => 'Van from Birulia bazaar at 5:30 am']],
            [$karim, 5, 'open', ['title' => 'Carry paddy bags to the road', 'work_type' => 'harvesting', 'location' => 'Birulia, Savar, Dhaka',
                'description' => 'Carry filled paddy bags from the field to the truck on the main road, about 200 metres.',
                'required_workers' => 4, 'proposed_wage' => 30, 'wage_basis' => 'unit', 'unit_label' => 'bag']],
            [$karim, 9, 'open', ['title' => 'Weed the brinjal field', 'work_type' => 'weeding', 'location' => 'Dhamrai, Dhaka',
                'description' => 'Hand weeding of one bigha of brinjal. The whole field must be finished for the agreed amount.',
                'required_workers' => 3, 'proposed_wage' => 1500, 'wage_basis' => 'task', 'meal_details' => 'No meals. Drinking water is available']],
            [$salma, 3, 'open', ['title' => 'Plant Boro rice seedlings', 'work_type' => 'planting', 'location' => 'Shibganj, Bogura',
                'description' => 'Transplant Boro seedlings on 2 bigha. Experience with line planting is preferred.',
                'required_workers' => 10, 'proposed_wage' => 650, 'wage_basis' => 'daily', 'meal_details' => 'Breakfast and lunch provided']],
            [$salma, 6, 'open', ['title' => 'Spray the mango orchard', 'work_type' => 'spraying', 'location' => 'Shibganj, Chapainawabganj',
                'description' => 'Spray 40 mango trees. The sprayer, mask and gloves are provided.',
                'required_workers' => 2, 'proposed_wage' => 900, 'wage_basis' => 'daily', 'transport_details' => 'Workers arrange their own transport']],
            [$salma, 12, 'open', ['title' => 'Dig irrigation channels', 'work_type' => 'irrigation', 'location' => 'Shibganj, Bogura',
                'description' => 'Dig and clean the water channels around a 4 bigha potato field before irrigation.',
                'required_workers' => 5, 'proposed_wage' => 2500, 'wage_basis' => 'task']],
            [$karim, 4, 'closed', ['title' => 'Spread urea on the wheat field', 'work_type' => 'fertilizer', 'location' => 'Dhamrai, Dhaka',
                'description' => 'Spread urea evenly on 2 bigha of wheat. Closed because enough workers were found.',
                'required_workers' => 2, 'proposed_wage' => 600, 'wage_basis' => 'daily']],
            [$salma, -5, 'open', ['title' => 'Harvest potatoes', 'work_type' => 'harvesting', 'location' => 'Shibganj, Bogura',
                'description' => 'Dig and sort potatoes. This job is in the past, so workers no longer see it.',
                'required_workers' => 6, 'proposed_wage' => 40, 'wage_basis' => 'unit', 'unit_label' => 'maund']],
            [$karim, -10, 'closed', ['title' => 'Clear the pond bank', 'work_type' => 'other', 'location' => 'Birulia, Savar, Dhaka',
                'description' => 'Cut the grass and bushes around the pond.',
                'required_workers' => 2, 'proposed_wage' => 550, 'wage_basis' => 'daily']],
        ];

        foreach ($jobs as [$farmer, $days, $status, $fields]) {
            $job = $farmer->farmJobs()->make($fields + ['work_date' => $today->copy()->addDays($days)]);
            $job->status = $status; // not fillable, so it is set directly
            $job->save();
        }

        $this->command?->info('Added '.count($jobs).' demo farm jobs. Log in as 01700000001 (farmer) or 01700000003 (worker), password "password".');
    }

    /** Find the demo account by phone, or create it. An existing account is left exactly as it is. */
    private function demoUser(string $phone, string $name, array $roles): User
    {
        $user = User::firstOrCreate(['phone' => $phone], ['name' => $name, 'password' => 'password', 'locale' => 'en']);

        if ($user->wasRecentlyCreated) {
            $user->roles()->attach(Role::whereIn('slug', $roles)->pluck('id'));
        }

        return $user;
    }
}
