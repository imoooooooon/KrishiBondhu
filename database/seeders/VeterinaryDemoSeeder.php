<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\VeterinarianProfile;
use App\Models\VisitRequest;
use Illuminate\Database\Seeder;

/**
 * Demo data for F18 (veterinary search and visit requests), for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=VeterinaryDemoSeeder
 *
 * Creates three demo veterinarians (two listed, one hidden from the directory) and visit requests from the demo
 * farmers in every state: pending, accepted, declined and cancelled. Password for every account: "password".
 * Safe to run twice: existing accounts are never changed, and nothing is added if a demo vet already has a profile.
 */
class VeterinaryDemoSeeder extends Seeder
{
    use DemoAccounts;

    public function run(): void
    {
        if ($this->refuseInProduction()) {
            return;
        }

        $karim = $this->demoUser('01700000001', 'Karim Mia (demo farmer)', [Role::FARMER]);
        $salma = $this->demoUser('01700000002', 'Salma Khatun (demo farmer and worker)', [Role::FARMER, Role::WORKER]);
        $hamid = $this->demoUser('01700000004', 'Abdul Hamid (demo farmer)', [Role::FARMER]);

        $vets = [
            '01700000007' => ['Dr. Farhana Yasmin (demo vet)', [
                'district' => 'Dhaka', 'upazila' => 'Savar', 'address' => 'Savar Bazar Road, near the upazila livestock office',
                'treats_cattle' => true, 'treats_goats' => true, 'treats_poultry' => false, 'experience_years' => 9, 'is_listed' => true,
                'bio' => 'Large-animal vet. Dairy cattle health, vaccination, calving problems and goat care. Farm visits in Savar and Dhamrai.',
            ]],
            '01700000008' => ['Dr. Anisur Rahman (demo vet)', [
                'district' => 'Bogura', 'upazila' => 'Shibganj', 'address' => 'Mohasthan Hat, Shibganj',
                'treats_cattle' => true, 'treats_goats' => false, 'treats_poultry' => true, 'experience_years' => 15, 'is_listed' => true,
                'bio' => 'Cattle and poultry. Regular visits to small dairy farms and layer farms around Shibganj and Bogura Sadar.',
            ]],
            '01700000009' => ['Dr. Shirin Sultana (demo vet, not listed)', [
                'district' => 'Jashore', 'upazila' => 'Jhikargacha', 'address' => 'College Road, Jhikargacha',
                'treats_cattle' => false, 'treats_goats' => true, 'treats_poultry' => true, 'experience_years' => 4, 'is_listed' => false,
                'bio' => 'Goats and poultry. Not taking new farms this month, so the profile is hidden from the directory.',
            ]],
        ];

        $users = collect($vets)->map(fn ($vet, $phone) => $this->demoUser((string) $phone, $vet[0], [Role::VETERINARIAN]));

        if (VeterinarianProfile::whereIn('user_id', $users->pluck('id'))->exists()) {
            $this->command?->info('Demo veterinarian profiles already exist. Nothing was added.');

            return;
        }

        $profiles = [];
        foreach ($vets as $phone => [, $fields]) {
            $profile = new VeterinarianProfile($fields);
            $profile->user_id = $users[$phone]->id;
            $profile->save();
            $profiles[$phone] = $profile;
        }

        $farhana = $profiles['01700000007'];
        $anis = $profiles['01700000008'];

        // [farmer, vet, status, preferred date (days from today), sent (days ago), fields, vet note]
        $requests = [
            [$karim, $farhana, VisitRequest::STATUS_PENDING, 2, 0, ['animal_type' => 'cattle', 'farm_address' => 'Birulia, Savar, Dhaka',
                'problem' => 'My cow Moni has eaten less for two days and gives about half her usual milk. No fever that I can feel.'], null],
            [$karim, $farhana, VisitRequest::STATUS_ACCEPTED, 1, 3, ['animal_type' => 'goat', 'farm_address' => 'Birulia, Savar, Dhaka',
                'problem' => 'Two of my goats are coughing and have a runny nose since last week.'], 'I will come in the morning, around 10 am. Keep the coughing goats apart from the others until then.'],
            [$hamid, $farhana, VisitRequest::STATUS_DECLINED, 3, 4, ['animal_type' => 'cattle', 'farm_address' => 'Sujanagar, Pabna',
                'problem' => 'Bull has a swollen front leg and is limping.'], 'Sorry, Pabna is outside the area I can visit. Please contact the Sujanagar upazila livestock office.'],
            [$salma, $anis, VisitRequest::STATUS_PENDING, 4, 1, ['animal_type' => 'poultry', 'farm_address' => 'Shibganj, Bogura',
                'problem' => 'About 20 of my 300 layer hens have stopped laying and look weak. Three died this week.'], null],
            [$salma, $anis, VisitRequest::STATUS_ACCEPTED, 6, 2, ['animal_type' => 'cattle', 'farm_address' => 'Shibganj, Bogura',
                'problem' => 'Pregnancy check for my cow Shada, inseminated about three months ago.'], 'Accepted. I will bring the equipment for the check.'],
            [$karim, $anis, VisitRequest::STATUS_CANCELLED, 5, 6, ['animal_type' => 'cattle', 'farm_address' => 'Dhamrai, Dhaka',
                'problem' => 'Calf with loose motion.'], null],
        ];

        foreach ($requests as [$farmer, $vet, $status, $inDays, $sentDaysAgo, $fields, $note]) {
            $request = new VisitRequest($fields + ['preferred_date' => now()->addDays($inDays)->toDateString()]);
            $request->farmer_id = $farmer->id;
            $request->veterinarian_profile_id = $vet->id;
            $request->status = $status;
            $request->vet_note = $note;
            $sent = now()->subDays($sentDaysAgo)->subHours(2);
            $request->created_at = $sent;
            $request->updated_at = $sent;
            if (in_array($status, [VisitRequest::STATUS_ACCEPTED, VisitRequest::STATUS_DECLINED], true)) {
                $request->responded_at = $sent->copy()->addHours(5);
                $request->updated_at = $request->responded_at;
            }
            $request->save();
        }

        $this->command?->info('Added 3 demo vets and '.count($requests).' visit requests.');
        $this->command?->info('Vets: 01700000007 (Farhana, Dhaka), 01700000008 (Anis, Bogura), 01700000009 (Shirin, not listed). Password: "password".');
    }
}
