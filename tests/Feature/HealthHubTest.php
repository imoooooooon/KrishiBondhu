<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_hub_with_links_to_the_built_health_pages(): void
    {
        $this->get(route('health.index'))
            ->assertOk()
            ->assertDontSee('Planned page')
            ->assertSee(route('guidance.soil.create'), false)
            ->assertSee(route('vets.index'), false)
            ->assertSee(route('treatments.index'), false);
    }

    public function test_planned_areas_are_shown_as_coming_soon_and_not_linked(): void
    {
        $this->get(route('health.index'))
            ->assertOk()
            ->assertSee('Crop disease identification')
            ->assertSee('Animal health guidance')
            ->assertSee('Coming soon')
            ->assertDontSee(route('guidance.crop-health.index'), false)
            ->assertDontSee(route('guidance.animal-health.index'), false);
    }

    public function test_hub_renders_in_bangla(): void
    {
        Role::firstOrCreate(['slug' => Role::FARMER], ['name' => 'Farmer']);
        $farmer = User::factory()->withRoles(Role::FARMER)->create(['locale' => 'bn']);

        $this->actingAs($farmer)
            ->get(route('health.index'))
            ->assertOk()
            ->assertSee('ফসল ও পশুর স্বাস্থ্য')
            ->assertSee('শীঘ্রই আসছে')
            ->assertDontSee('Coming soon');
    }
}
