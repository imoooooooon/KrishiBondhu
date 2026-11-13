<?php

namespace Tests\Feature\Cattle;

use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Services\CattleAge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F6: editing, age keep/replace, photo replacement, health history, stale tabs, archive and restore. */
class ManageCattleTest extends TestCase
{
    use CattleHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_editing_a_name_keeps_the_kb_id_and_the_age_estimate_date(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->estimated(24, '2026-01-10')->create();
        $id = $cattle->public_id;

        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), $this->editForm($cattle, [
            'nickname' => 'Moni', 'estimate_years' => '9', 'age_basis' => 'unknown', // ignored: age_update=keep
        ]))->assertRedirect(route('cattle.show', $cattle))->assertSessionHasNoErrors();

        $fresh = $cattle->fresh();
        $this->assertSame(['Moni', $id, 2], [$fresh->nickname, $fresh->public_id, $fresh->version]);
        $this->assertSame(['estimated', 24, '2026-01-10'], [$fresh->age_basis, $fresh->estimated_age_months, $fresh->age_recorded_on->toDateString()]);
    }

    public function test_replacing_the_age_sets_a_new_estimate_as_of_today(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create(); // exact birth date

        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), $this->editForm($cattle, [
            'age_update' => 'replace', 'age_basis' => 'estimated', 'estimate_years' => '1', 'estimate_months' => '3',
        ]))->assertSessionHasNoErrors();

        $fresh = $cattle->fresh();
        $this->assertSame(['estimated', 15, CattleAge::today(), null], [$fresh->age_basis, $fresh->estimated_age_months, $fresh->age_recorded_on->toDateString(), $fresh->birth_date]);
    }

    public function test_a_birth_date_after_a_recorded_health_event_is_refused(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->unknownAge()->create();
        $this->actingAs($farmer)->post(route('cattle.health-history.store', $cattle), $this->entryForm(['occurred_on' => $this->day(-100)]));

        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), $this->editForm($cattle, [
            'age_update' => 'replace', 'age_basis' => 'exact', 'birth_date' => $this->day(-50),
        ]))->assertSessionHasErrors('birth_date');

        $this->assertSame('unknown', $cattle->fresh()->age_basis);
    }

    public function test_replacing_the_photo_deletes_the_old_files_after_saving(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create();
        $old = [$cattle->photo_path, $cattle->photo_thumbnail_path];

        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), $this->editForm($cattle, ['photo' => UploadedFile::fake()->image('new.jpg', 800, 600)]))
            ->assertSessionHasNoErrors();

        $fresh = $cattle->fresh();
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists([$fresh->photo_path, $fresh->photo_thumbnail_path]);
        $this->assertSame(800, $fresh->photo_width);
    }

    public function test_a_save_from_an_old_tab_is_refused(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create();
        $tabA = $this->editForm($cattle, ['nickname' => 'First save']);
        $tabB = $this->editForm($cattle, ['nickname' => 'Old tab', 'photo' => UploadedFile::fake()->image('x.jpg')]);

        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), $tabA);
        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), $tabB)->assertStatus(409)->assertSee('Your changes were not saved.')->assertSee('Old tab');

        $this->assertSame('First save', $cattle->fresh()->nickname);
        $this->assertCount(2, Storage::disk('local')->allFiles(Cattle::PHOTO_DIRECTORY), 'the stale request’s new photo was deleted');
    }

    public function test_health_entries_dates_order_duplicates_and_corrections(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create(['birth_date' => $this->day(-400)]);
        $url = route('cattle.health-history.store', $cattle);

        $this->actingAs($farmer)->post($url, $this->entryForm(['occurred_on' => $this->day(1)]))->assertSessionHasErrors('occurred_on');
        $this->actingAs($farmer)->post($url, $this->entryForm(['occurred_on' => $this->day(-500)]))->assertSessionHasErrors('occurred_on'); // before birth

        $form = $this->entryForm(['title' => 'Older visit', 'occurred_on' => $this->day(-200)]);
        $this->actingAs($farmer)->post($url, $form)->assertSessionHasNoErrors();
        $this->actingAs($farmer)->post($url, $form)->assertSessionHas('status', 'This entry was already saved from your earlier submission. Nothing was added twice.');
        $this->actingAs($farmer)->post($url, $this->entryForm(['title' => 'Undated note', 'date_known' => 'no', 'occurred_on' => $this->day(-5)]));
        $this->actingAs($farmer)->post($url, $this->entryForm(['title' => 'Newest visit', 'occurred_on' => $this->day(-2)]));

        $this->assertSame(3, CattleHealthEntry::count());
        $this->assertNull(CattleHealthEntry::where('title', 'Undated note')->value('occurred_on'), 'an unknown date is not turned into today');
        $this->actingAs($farmer)->get(route('cattle.show', $cattle))->assertSeeInOrder(['Newest visit', 'Older visit', 'Date not recorded', 'Undated note']);

        $entry = CattleHealthEntry::where('title', 'Older visit')->first();
        $edit = $this->entryForm(['version' => 1, 'title' => 'Older visit (corrected)', 'occurred_on' => $this->day(-200)]);
        $this->actingAs($farmer)->patch(route('cattle.health-history.update', [$cattle, $entry]), $edit)->assertSessionHasNoErrors();
        $this->actingAs($farmer)->patch(route('cattle.health-history.update', [$cattle, $entry]), $edit)->assertStatus(409); // old version again

        $fresh = $entry->fresh();
        $this->assertSame(['Older visit (corrected)', 2, $farmer->id], [$fresh->title, $fresh->version, $fresh->recorded_by]);
        $this->assertTrue($fresh->wasEdited());
    }

    public function test_archive_keeps_everything_blocks_changes_and_restore_brings_it_back(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create(['nickname' => 'Kalo']);
        $id = $cattle->public_id;

        $this->actingAs($farmer)->get(route('cattle.show', [$cattle, 'confirm' => 'archive']))->assertOk()->assertSee('Archive this animal?');
        $this->assertNull($cattle->fresh()->archived_at, 'opening the confirmation changes nothing');
        $this->actingAs($farmer)->delete(route('cattle.destroy', $cattle), ['version' => 1])->assertSessionHasErrors('confirm_archive');
        $this->actingAs($farmer)->delete(route('cattle.destroy', $cattle), ['confirm_archive' => '1', 'version' => 1])->assertRedirect(route('cattle.index'));

        $this->actingAs($farmer)->get(route('cattle.index'))->assertDontSee('Kalo');
        $this->actingAs($farmer)->get(route('cattle.index', ['scope' => 'archived']))->assertSee('Kalo');
        $this->actingAs($farmer)->get(route('cattle.show', $cattle))->assertOk()->assertSee('Archived');
        $this->actingAs($farmer)->get($cattle->photoUrl('thumb'))->assertOk(); // the owner can still see it

        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), $this->editForm($cattle->fresh(), ['nickname' => 'X']))->assertSessionHasErrors('cattle');
        $this->actingAs($farmer)->post(route('cattle.health-history.store', $cattle), $this->entryForm())->assertSessionHasErrors('cattle');
        $this->actingAs($farmer)->get(route('cattle.edit', $cattle))->assertRedirect(route('cattle.show', $cattle));

        $this->actingAs($farmer)->patch(route('cattle.update', $cattle), ['operation' => 'restore', 'version' => 2, 'nickname' => 'Sneaky'])->assertSessionHasNoErrors();
        $fresh = $cattle->fresh();
        $this->assertSame([null, 'Kalo', $id, 3], [$fresh->archived_at, $fresh->nickname, $fresh->public_id, $fresh->version], 'restore changes nothing else');
    }

    public function test_connected_features_are_marked_planned_and_their_pages_say_not_built(): void
    {
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create();

        $this->actingAs($farmer)->get(route('cattle.show', $cattle))->assertOk()
            ->assertSee(route('cattle.family-tree', $cattle), false)
            ->assertSee(route('cattle.inseminations.index', $cattle), false)
            ->assertSee('Planned');
        $this->actingAs($farmer)->get(route('cattle.family-tree', $cattle))->assertOk()->assertSee('has not been built yet');
    }

    public function test_exact_age_display_is_frozen_in_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-13 06:00:00', 'UTC'));
        $farmer = $this->farmer();
        $cattle = Cattle::factory()->for($farmer, 'farmer')->create(['birth_date' => '2023-09-13']);

        $this->actingAs($farmer)->get(route('cattle.show', $cattle))->assertSee('3 years 2 months')->assertSee('Exact: born 13 Sep 2023');
        Carbon::setTestNow();
    }
}
