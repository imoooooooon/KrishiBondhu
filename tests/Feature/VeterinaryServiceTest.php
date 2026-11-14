<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VeterinarianProfile;
use App\Models\VisitRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VeterinaryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => Role::FARMER], ['name' => 'Farmer']);
        Role::firstOrCreate(['slug' => Role::VETERINARIAN], ['name' => 'Veterinarian']);
        Role::firstOrCreate(['slug' => Role::BUYER], ['name' => 'Buyer']);
    }

    public function test_veterinarian_can_create_and_update_profile(): void
    {
        $vet = User::factory()->withRoles(Role::VETERINARIAN)->create();

        $this->actingAs($vet)
            ->get(route('vet.profile.edit'))
            ->assertOk();

        $this->actingAs($vet)
            ->patch(route('vet.profile.update'), [
                'district' => 'Dhaka',
                'upazila' => 'Savar',
                'address' => 'Savar Bazar',
                'treats_cattle' => '1',
                'experience_years' => 5,
                'bio' => 'Cattle specialist',
                'is_listed' => '1',
            ])
            ->assertRedirect(route('vet.profile.edit'));

        $this->assertDatabaseHas('veterinarian_profiles', [
            'user_id' => $vet->id,
            'district' => 'Dhaka',
            'upazila' => 'Savar',
            'treats_cattle' => true,
            'is_listed' => true,
        ]);
    }

    public function test_non_veterinarian_cannot_access_vet_profile_page(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();

        $this->actingAs($farmer)
            ->get(route('vet.profile.edit'))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->patch(route('vet.profile.update'), [
                'district' => 'Dhaka',
                'upazila' => 'Savar',
                'address' => 'Savar Bazar',
            ])
            ->assertForbidden();
    }

    public function test_vet_without_profile_cannot_open_incoming_visit_requests(): void
    {
        $vet = User::factory()->withRoles(Role::VETERINARIAN)->create();

        $this->actingAs($vet)
            ->get(route('vet.visit-requests.index'))
            ->assertForbidden();
    }

    public function test_public_vet_directory_lists_and_filters_listed_vets_and_hides_unlisted(): void
    {
        $vetUser1 = User::factory()->withRoles(Role::VETERINARIAN)->create(['name' => 'Dr. Rahim']);
        $listedProfile = new VeterinarianProfile([
            'district' => 'Bogura',
            'upazila' => 'Sherpur',
            'address' => 'College Road',
            'treats_cattle' => true,
            'is_listed' => true,
        ]);
        $listedProfile->user_id = $vetUser1->id;
        $listedProfile->save();

        $vetUser2 = User::factory()->withRoles(Role::VETERINARIAN)->create(['name' => 'Dr. Karim']);
        $unlistedProfile = new VeterinarianProfile([
            'district' => 'Sylhet',
            'upazila' => 'Beanibazar',
            'address' => 'Station Road',
            'treats_poultry' => true,
            'is_listed' => false,
        ]);
        $unlistedProfile->user_id = $vetUser2->id;
        $unlistedProfile->save();

        $this->get(route('vets.index'))
            ->assertOk()
            ->assertSee('Dr. Rahim')
            ->assertDontSee('Dr. Karim');

        $this->get(route('vets.index', ['district' => 'Bogura', 'animal' => 'cattle']))
            ->assertOk()
            ->assertSee('Dr. Rahim');

        $this->get(route('vets.show', $listedProfile))->assertOk();
        $this->get(route('vets.show', $unlistedProfile))->assertNotFound();
    }

    public function test_farmer_can_submit_visit_request_and_cannot_duplicate_pending_request(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();
        $vetUser = User::factory()->withRoles(Role::VETERINARIAN)->create();
        $profile = new VeterinarianProfile([
            'district' => 'Cumilla',
            'upazila' => 'Daudkandi',
            'address' => 'Main Road',
            'is_listed' => true,
        ]);
        $profile->user_id = $vetUser->id;
        $profile->save();

        $this->actingAs($farmer)
            ->post(route('vets.visit-requests.store', $profile), [
                'animal_type' => 'invalid_animal',
                'preferred_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['animal_type', 'problem', 'preferred_date', 'farm_address']);

        $this->actingAs($farmer)
            ->post(route('vets.visit-requests.store', $profile), [
                'animal_type' => 'cattle',
                'problem' => 'High fever and loss of appetite',
                'preferred_date' => now()->addDay()->toDateString(),
                'farm_address' => 'Village North, Daudkandi',
            ])
            ->assertRedirect(route('visit-requests.index'));

        $this->actingAs($farmer)
            ->post(route('vets.visit-requests.store', $profile), [
                'animal_type' => 'goat',
                'problem' => 'Another issue',
                'preferred_date' => now()->addDays(2)->toDateString(),
                'farm_address' => 'Village North, Daudkandi',
            ])
            ->assertSessionHasErrors(['veterinarian_profile_id']);
    }

    public function test_only_owner_farmer_or_assigned_vet_can_view_visit_request_details(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();
        $otherFarmer = User::factory()->withRoles(Role::FARMER)->create();
        $vetUser = User::factory()->withRoles(Role::VETERINARIAN)->create();

        $profile = new VeterinarianProfile([
            'district' => 'Barishal',
            'upazila' => 'Sadar',
            'address' => 'Hospital Road',
            'is_listed' => true,
        ]);
        $profile->user_id = $vetUser->id;
        $profile->save();

        $requestRecord = new VisitRequest([
            'animal_type' => 'cattle',
            'problem' => 'Foot injury',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'farm_address' => 'Barishal Farm',
        ]);
        $requestRecord->farmer_id = $farmer->id;
        $requestRecord->veterinarian_profile_id = $profile->id;
        $requestRecord->save();

        $this->actingAs($farmer)->get(route('visit-requests.show', $requestRecord))->assertOk();
        $this->actingAs($vetUser)->get(route('visit-requests.show', $requestRecord))->assertOk();
        $this->actingAs($otherFarmer)->get(route('visit-requests.show', $requestRecord))->assertForbidden();
    }

    public function test_assigned_vet_can_accept_or_decline_and_other_vet_is_forbidden(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();
        $vetUser = User::factory()->withRoles(Role::VETERINARIAN)->create();
        $otherVet = User::factory()->withRoles(Role::VETERINARIAN)->create();

        $profile = new VeterinarianProfile([
            'district' => 'Rajshahi',
            'upazila' => 'Paba',
            'address' => 'Paba Sadar',
            'is_listed' => true,
        ]);
        $profile->user_id = $vetUser->id;
        $profile->save();

        $requestRecord = new VisitRequest([
            'animal_type' => 'goat',
            'problem' => 'Limping leg',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'farm_address' => 'Paba Village',
        ]);
        $requestRecord->farmer_id = $farmer->id;
        $requestRecord->veterinarian_profile_id = $profile->id;
        $requestRecord->save();

        $this->actingAs($otherVet)
            ->patch(route('vet.visit-requests.update', $requestRecord), [
                'status' => VisitRequest::STATUS_ACCEPTED,
            ])
            ->assertForbidden();

        $this->actingAs($vetUser)
            ->patch(route('vet.visit-requests.update', $requestRecord), [
                'status' => VisitRequest::STATUS_ACCEPTED,
                'vet_note' => 'Will visit at 10 AM',
            ])
            ->assertRedirect(route('vet.visit-requests.index'));

        $this->assertDatabaseHas('visit_requests', [
            'id' => $requestRecord->id,
            'status' => VisitRequest::STATUS_ACCEPTED,
            'vet_note' => 'Will visit at 10 AM',
        ]);
    }

    public function test_farmer_can_cancel_pending_request_but_not_accepted_request(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();
        $otherFarmer = User::factory()->withRoles(Role::FARMER)->create();
        $vetUser = User::factory()->withRoles(Role::VETERINARIAN)->create();

        $profile = new VeterinarianProfile([
            'district' => 'Khulna',
            'upazila' => 'Dumuria',
            'address' => 'Dumuria Bazar',
            'is_listed' => true,
        ]);
        $profile->user_id = $vetUser->id;
        $profile->save();

        $requestRecord = new VisitRequest([
            'animal_type' => 'cattle',
            'problem' => 'Skin rash',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'farm_address' => 'Dumuria Farm',
        ]);
        $requestRecord->farmer_id = $farmer->id;
        $requestRecord->veterinarian_profile_id = $profile->id;
        $requestRecord->save();

        $this->actingAs($otherFarmer)
            ->patch(route('visit-requests.cancel', $requestRecord))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->patch(route('visit-requests.cancel', $requestRecord))
            ->assertRedirect(route('visit-requests.index'));

        $this->assertDatabaseHas('visit_requests', [
            'id' => $requestRecord->id,
            'status' => VisitRequest::STATUS_CANCELLED,
        ]);

        $this->actingAs($farmer)
            ->patch(route('visit-requests.cancel', $requestRecord))
            ->assertForbidden();
    }

    public function test_vet_directory_and_visit_request_pages_render_in_bangla(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create(['locale' => 'bn']);
        $vetUser = User::factory()->withRoles(Role::VETERINARIAN)->create(['locale' => 'bn']);

        $profile = new VeterinarianProfile([
            'district' => 'Rajshahi',
            'upazila' => 'Paba',
            'address' => 'Station Road',
            'treats_goats' => true,
            'is_listed' => true,
        ]);
        $profile->user_id = $vetUser->id;
        $profile->save();

        $requestRecord = new VisitRequest([
            'animal_type' => 'goat',
            'problem' => 'Not eating',
            'preferred_date' => now()->addDay()->toDateString(),
            'farm_address' => 'Paba Farm',
        ]);
        $requestRecord->farmer_id = $farmer->id;
        $requestRecord->veterinarian_profile_id = $profile->id;
        $requestRecord->save();

        $this->actingAs($farmer)->get(route('vets.index'))
            ->assertOk()
            ->assertSee('পশু চিকিৎসক')
            ->assertSee('নাম বা উপজেলা দিয়ে খুঁজুন')
            ->assertDontSee('Veterinary Doctors');

        $this->actingAs($farmer)->get(route('vets.show', $profile))
            ->assertOk()
            ->assertSee('খামারে ভিজিটের অনুরোধ করুন')
            ->assertSee('ছাগল');

        // The status badge is translated, not the raw database value.
        $this->actingAs($farmer)->get(route('visit-requests.show', $requestRecord))
            ->assertOk()
            ->assertSee('অপেক্ষমাণ')
            ->assertDontSee('PENDING');

        $this->actingAs($vetUser)->get(route('vet.visit-requests.index'))
            ->assertOk()
            ->assertSee('আসা ভিজিটের অনুরোধ')
            ->assertSee('গ্রহণ করুন');
    }
}
