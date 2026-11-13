<?php

namespace Tests\Feature\Cattle;

use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** F6 spec §5 and §16: cattle records are private. Every request is sent directly, as an attacker would. */
class CattleAccessTest extends TestCase
{
    use CattleHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function entryFor(Cattle $cattle): CattleHealthEntry
    {
        $entry = $cattle->healthEntries()->make(['entry_type' => 'observation', 'title' => 'Limping', 'details' => 'Left back leg.', 'occurred_on' => $this->day(-3)]);
        $entry->recorded_by = $cattle->farmer_id;
        $entry->submission_token = (string) Str::uuid();
        $entry->save();

        return $entry;
    }

    public function test_guests_are_sent_to_log_in_everywhere(): void
    {
        $cattle = Cattle::factory()->create();

        foreach ([
            $this->get(route('cattle.index')), $this->get(route('cattle.create')), $this->get(route('cattle.show', $cattle)),
            $this->get(route('cattle.edit', $cattle)), $this->get($cattle->photoUrl('thumb')),
            $this->post(route('cattle.store'), $this->registerForm()),
            $this->patch(route('cattle.update', $cattle), ['operation' => 'restore', 'version' => 1]),
            $this->delete(route('cattle.destroy', $cattle), ['confirm_archive' => '1', 'version' => 1]),
        ] as $response) {
            $response->assertRedirect(route('login'));
        }
    }

    public function test_non_farmers_have_no_cattle_workspace(): void
    {
        $buyer = User::factory()->withRoles(Role::BUYER)->create();
        $cattle = Cattle::factory()->create();

        $this->actingAs($buyer)->get(route('cattle.index'))->assertForbidden();
        $this->actingAs($buyer)->post(route('cattle.store'), $this->registerForm())->assertForbidden();
        $this->actingAs($buyer)->get(route('cattle.show', $cattle))->assertForbidden();
        $this->assertSame(1, Cattle::count());
    }

    public function test_a_farmer_with_several_roles_is_a_normal_owner(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER, Role::BUYER, Role::WORKER)->create();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create();

        $this->actingAs($farmer)->get(route('cattle.show', $cattle))->assertOk();
    }

    public function test_another_farmer_gets_not_found_for_every_route(): void
    {
        $owner = $this->farmer();
        $intruder = $this->farmer();
        $cattle = Cattle::factory()->for($owner, 'farmer')->create(['nickname' => 'Secret Lali']);
        $entry = $this->entryFor($cattle);

        foreach ([
            $this->actingAs($intruder)->get(route('cattle.show', $cattle)),
            $this->actingAs($intruder)->get(route('cattle.edit', $cattle)),
            $this->actingAs($intruder)->get($cattle->photoUrl('display')),
            $this->actingAs($intruder)->get(route('cattle.health-history.create', $cattle)),
            $this->actingAs($intruder)->get(route('cattle.health-history.edit', [$cattle, $entry])),
            $this->actingAs($intruder)->patch(route('cattle.update', $cattle), $this->editForm($cattle, ['nickname' => 'Stolen'])),
            $this->actingAs($intruder)->delete(route('cattle.destroy', $cattle), ['confirm_archive' => '1', 'version' => 1]),
            $this->actingAs($intruder)->post(route('cattle.health-history.store', $cattle), $this->entryForm()),
            $this->actingAs($intruder)->patch(route('cattle.health-history.update', [$cattle, $entry]), $this->entryForm(['version' => 1, 'title' => 'Changed'])),
        ] as $response) {
            $response->assertNotFound()->assertDontSee('Secret Lali');
        }

        $this->assertSame(['Secret Lali', 1, null], [$cattle->fresh()->nickname, $cattle->fresh()->version, $cattle->fresh()->archived_at]);
        $this->assertSame(['Limping', 1], [$entry->fresh()->title, CattleHealthEntry::count()]);
    }

    public function test_an_entry_of_one_animal_cannot_be_reached_through_another(): void
    {
        $farmer = $this->farmer();
        $a = Cattle::factory()->for($farmer, 'farmer')->create();
        $b = Cattle::factory()->for($farmer, 'farmer')->create();
        $entryOfB = $this->entryFor($b);

        $this->actingAs($farmer)->get(route('cattle.health-history.edit', [$a, $entryOfB]))->assertNotFound();
        $this->actingAs($farmer)->patch(route('cattle.health-history.update', [$a, $entryOfB]), $this->entryForm(['version' => 1, 'title' => 'Moved']))->assertNotFound();

        $this->assertSame([$b->id, 'Limping'], [$entryOfB->fresh()->cattle_id, $entryOfB->fresh()->title]);
    }

    public function test_search_and_counts_never_include_another_farmers_cattle(): void
    {
        $farmer = $this->farmer();
        $other = $this->farmer();
        Cattle::factory()->for($farmer, 'farmer')->create(['nickname' => 'Moni']);
        $theirs = Cattle::factory()->for($other, 'farmer')->create(['nickname' => 'Moni of the neighbour', 'farm_tag' => 'N-1']);

        $this->actingAs($farmer)->get(route('cattle.index', ['q' => 'Moni']))->assertOk()->assertSee('Moni')->assertDontSee('of the neighbour');
        $this->actingAs($farmer)->get(route('cattle.index', ['q' => $theirs->displayId()]))->assertOk()->assertSee('No cattle match your search');
        $this->actingAs($farmer)->get(route('cattle.index', ['q' => '%']))->assertOk()->assertSee('No cattle match your search'); // % is a normal character
        $this->actingAs($farmer)->get(route('cattle.index', ['scope' => 'all']))->assertOk()->assertDontSee('of the neighbour');
    }

    public function test_farmer_text_is_escaped(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create(['nickname' => '<script>alert(1)</script>Lali', 'mother_details' => '<img src=x onerror=alert(2)>']);

        foreach ([route('cattle.index'), route('cattle.show', $cattle), route('cattle.edit', $cattle)] as $url) {
            $this->actingAs($farmer)->get($url)->assertOk()
                ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror', false)
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;Lali', false);
        }
    }

    public function test_forged_writes_are_rejected_with_csrf_protection_on(): void
    {
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create();

        $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm())->assertStatus(419);
        $this->actingAs($farmer)->delete(route('cattle.destroy', $cattle), ['confirm_archive' => '1', 'version' => 1])->assertStatus(419);
        $this->assertNull($cattle->fresh()->archived_at);
    }

    public function test_registration_is_rate_limited(): void
    {
        $farmer = $this->farmer();
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($farmer)->post(route('cattle.store'), [])->assertRedirect();
        }
        $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm())->assertStatus(429);
        $this->assertSame(0, Cattle::count());
    }

    public function test_the_herd_list_uses_the_same_number_of_queries_for_1_or_12_cattle(): void
    {
        $farmer = $this->farmer();
        Cattle::factory()->for($farmer, 'farmer')->create();
        $count = function () use ($farmer) {
            \DB::flushQueryLog();
            \DB::enableQueryLog();
            $this->actingAs($farmer->fresh())->get(route('cattle.index'))->assertOk();

            return count(\DB::getQueryLog());
        };
        $one = $count();
        Cattle::factory()->count(11)->for($farmer, 'farmer')->create();

        $this->assertSame($one, $count());
    }
}
