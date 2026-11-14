<?php

namespace Tests\Feature\SoilGuidance;

use App\Models\Role;
use App\Models\SoilGuidanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F15: soil guidance requests are private. Every request is sent directly, as an attacker would. */
class SoilGuidanceAccessTest extends TestCase
{
    use RefreshDatabase, SoilGuidanceHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSoilGuidance();
    }

    public function test_guests_are_sent_to_log_in_everywhere(): void
    {
        $record = SoilGuidanceRecord::factory()->failed()->withPhoto()->create();

        foreach ([
            $this->get(route('guidance.soil.index')),
            $this->get(route('guidance.soil.create')),
            $this->get(route('guidance.soil.show', $record)),
            $this->get(route('guidance.soil.photo', $record)),
            $this->post(route('guidance.soil.store'), $this->form()),
            $this->post(route('guidance.soil.retry', $record)),
        ] as $response) {
            $response->assertRedirect(route('login'));
        }

        $this->assertSame(1, SoilGuidanceRecord::count());
        Http::assertNothingSent();
    }

    public function test_accounts_without_the_farmer_role_cannot_use_soil_guidance(): void
    {
        $buyer = User::factory()->withRoles(Role::BUYER, Role::SELLER)->create();
        $record = SoilGuidanceRecord::factory()->failed()->withPhoto()->create();

        $this->actingAs($buyer)->get(route('guidance.soil.index'))->assertForbidden();
        $this->actingAs($buyer)->get(route('guidance.soil.create'))->assertForbidden();
        $this->actingAs($buyer)->post(route('guidance.soil.store'), $this->form())->assertForbidden();
        $this->actingAs($buyer)->get(route('guidance.soil.show', $record))->assertForbidden();
        $this->actingAs($buyer)->get(route('guidance.soil.photo', $record))->assertForbidden();
        $this->actingAs($buyer)->post(route('guidance.soil.retry', $record))->assertForbidden();

        $this->assertSame(1, SoilGuidanceRecord::count());
        Http::assertNothingSent();
    }

    public function test_a_farmer_with_other_roles_too_can_use_soil_guidance(): void
    {
        $this->fakeAiAnswer();
        $farmer = User::factory()->withRoles(Role::FARMER, Role::BUYER, Role::WORKER)->create();

        $this->actingAs($farmer)->get(route('guidance.soil.create'))->assertOk();
        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form())->assertRedirect();

        $record = SoilGuidanceRecord::sole();
        $this->assertSame($farmer->id, $record->farmer_id);
        $this->actingAs($farmer)->get(route('guidance.soil.show', $record))->assertOk();
        $this->actingAs($farmer)->get(route('guidance.soil.index'))->assertOk()->assertSee('Boro rice');
    }

    public function test_another_farmer_cannot_view_retry_or_get_the_photo(): void
    {
        $owner = $this->farmer();
        $intruder = $this->farmer();
        $record = SoilGuidanceRecord::factory()->for($owner, 'farmer')->failed()->withPhoto()->create(['crop_name' => 'Secret saffron']);

        foreach ([
            $this->actingAs($intruder)->get(route('guidance.soil.show', $record)),
            $this->actingAs($intruder)->get(route('guidance.soil.photo', $record)),
            $this->actingAs($intruder)->post(route('guidance.soil.retry', $record)),
        ] as $response) {
            $response->assertNotFound()->assertDontSee('Secret saffron');
        }

        $fresh = $record->fresh();
        $this->assertSame([SoilGuidanceRecord::STATUS_FAILED, 1], [$fresh->status, $fresh->attempt_count], 'nothing changed');
        Http::assertNothingSent();
    }

    public function test_owner_gets_the_private_photo_and_it_is_not_in_the_public_folder(): void
    {
        $owner = $this->farmer();
        $record = SoilGuidanceRecord::factory()->for($owner, 'farmer')->completed()->withPhoto()->create();

        $response = $this->actingAs($owner)->get(route('guidance.soil.photo', $record));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('local')->get($record->photo_path), $response->getContent());
        $this->assertFileDoesNotExist(public_path($record->photo_path));
    }

    public function test_photo_route_answers_not_found_without_a_photo_or_when_the_file_is_gone(): void
    {
        $owner = $this->farmer();
        $noPhoto = SoilGuidanceRecord::factory()->for($owner, 'farmer')->completed()->create();
        $lostFile = SoilGuidanceRecord::factory()->for($owner, 'farmer')->completed()->withPhoto()->create();
        Storage::disk('local')->delete($lostFile->photo_path);

        $this->actingAs($owner)->get(route('guidance.soil.photo', $noPhoto))->assertNotFound();
        $this->actingAs($owner)->get(route('guidance.soil.photo', $lostFile))->assertNotFound();
    }

    public function test_history_shows_only_the_signed_in_farmers_requests(): void
    {
        $farmer = $this->farmer();
        $other = $this->farmer();
        $mine = SoilGuidanceRecord::factory()->for($farmer, 'farmer')->completed()->create(['crop_name' => 'My potato field']);
        SoilGuidanceRecord::factory()->for($farmer, 'farmer')->failed()->create(['crop_name' => 'My jute field']);
        SoilGuidanceRecord::factory()->for($other, 'farmer')->completed()->create(['crop_name' => 'Neighbour mustard']);

        $this->actingAs($farmer)->get(route('guidance.soil.index'))
            ->assertOk()
            ->assertSee('My potato field')
            ->assertSee('Ready')
            ->assertSee('My jute field')
            ->assertSee('Failed')
            ->assertSee('View result')
            ->assertSee(route('guidance.soil.show', $mine))
            ->assertDontSee('Neighbour mustard');
    }

    public function test_history_is_paginated_newest_first(): void
    {
        $farmer = $this->farmer();
        foreach (range(1, 12) as $number) {
            SoilGuidanceRecord::factory()->for($farmer, 'farmer')->completed()
                ->create(['crop_name' => "Crop number {$number}.", 'created_at' => now()->subDays(13 - $number)]);
        }

        $this->actingAs($farmer)->get(route('guidance.soil.index'))
            ->assertOk()
            ->assertSeeInOrder(['Crop number 12.', 'Crop number 11.', 'Crop number 3.'])
            ->assertDontSee('Crop number 2.')
            ->assertSee('Page 1 of 2');

        $this->actingAs($farmer)->get(route('guidance.soil.index', ['page' => 2]))
            ->assertOk()
            ->assertSeeInOrder(['Crop number 2.', 'Crop number 1.'])
            ->assertDontSee('Crop number 3.');
    }

    public function test_history_has_a_helpful_empty_state(): void
    {
        $this->actingAs($this->farmer())->get(route('guidance.soil.index'))
            ->assertOk()
            ->assertSee('You have not asked for soil guidance yet')
            ->assertSee(route('guidance.soil.create'));
    }

    public function test_dashboard_links_farmers_to_soil_guidance(): void
    {
        $this->actingAs($this->farmer())->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('guidance.soil.create'))
            ->assertSee(route('guidance.soil.index'));

        $this->actingAs(User::factory()->withRoles(Role::BUYER)->create())->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('guidance.soil.create'));
    }
}
