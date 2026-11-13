<?php

namespace Tests\Feature\Cattle;

use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Services\CattleAge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F6: registering cattle and the checks before saving (spec §9, §13). */
class RegisterCattleTest extends TestCase
{
    use CattleHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_farmer_registers_cattle_with_a_first_health_note(): void
    {
        $farmer = $this->farmer();

        $response = $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm([
            'history_type' => 'vaccination', 'history_title' => 'FMD vaccine', 'history_date_known' => 'yes',
            'history_occurred_on' => $this->day(-10), 'history_details' => 'Upazila camp, first dose.', 'history_source' => 'Notebook',
        ]));

        $cattle = Cattle::sole();
        $response->assertRedirect(route('cattle.show', $cattle));
        $this->assertSame($farmer->id, $cattle->farmer_id);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $cattle->public_id, 'a ULID made by the server');
        $this->assertSame('exact', $cattle->age_basis);
        $this->assertNull($cattle->estimated_age_months);
        $this->assertNull($cattle->father_details, 'an empty field is stored as "not recorded"');
        Storage::disk('local')->assertExists([$cattle->photo_path, $cattle->photo_thumbnail_path]);
        $this->assertStringStartsWith(Cattle::PHOTO_DIRECTORY.'/', $cattle->photo_path);

        $entry = CattleHealthEntry::sole();
        $this->assertSame([$cattle->id, $farmer->id, 'FMD vaccine'], [$entry->cattle_id, $entry->recorded_by, $entry->title]);

        $this->actingAs($farmer)->get(route('cattle.show', $cattle))->assertOk()->assertSee($cattle->displayId())->assertSee('FMD vaccine');
    }

    public function test_system_fields_cannot_be_sent_in(): void
    {
        $farmer = $this->farmer();
        $other = $this->farmer();

        $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm([
            'farmer_id' => $other->id, 'public_id' => 'FORGEDFORGEDFORGEDFORGED00', 'version' => 9,
            'archived_at' => '2020-01-01 00:00:00', 'age_recorded_on' => '2001-01-01', 'photo_path' => '../../.env',
        ]));

        $cattle = Cattle::sole();
        $this->assertSame($farmer->id, $cattle->farmer_id);
        $this->assertNotSame('FORGEDFORGEDFORGEDFORGED00', $cattle->public_id);
        $this->assertSame(1, $cattle->version);
        $this->assertNull($cattle->archived_at);
        $this->assertStringStartsWith(Cattle::PHOTO_DIRECTORY.'/', $cattle->photo_path);
    }

    public function test_estimated_age_is_anchored_to_today_by_the_server(): void
    {
        $this->actingAs($this->farmer())->post(route('cattle.store'), $this->registerForm([
            'age_basis' => 'estimated', 'estimate_years' => '২', 'estimate_months' => '৬', 'birth_date' => '2020-01-01',
        ]))->assertSessionHasNoErrors();

        $cattle = Cattle::sole();
        $this->assertSame(30, $cattle->estimated_age_months, 'Bangla digits work; 2 years 6 months = 30');
        $this->assertSame(CattleAge::today(), $cattle->age_recorded_on->toDateString());
        $this->assertNull($cattle->birth_date, 'the birth date of the ignored option is not stored');
    }

    public function test_unknown_age_breed_and_parents_stay_unknown(): void
    {
        $farmer = $this->farmer();
        $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm([
            'age_basis' => 'unknown', 'breed_known' => 'no', 'breed_description' => 'should be dropped', 'mother_details' => '', 'farm_tag' => '',
        ]))->assertSessionHasNoErrors();

        $cattle = Cattle::sole();
        $this->assertSame([null, null, null, null], [$cattle->birth_date, $cattle->breed_description, $cattle->mother_details, $cattle->farm_tag]);
        $this->actingAs($farmer)->get(route('cattle.show', $cattle))
            ->assertSee('Age not recorded')
            ->assertSee('No health history recorded')
            ->assertSee('This does not confirm the animal’s health.')
            ->assertDontSee('Healthy');
    }

    public function test_invalid_input_is_refused_and_nothing_is_saved(): void
    {
        $farmer = $this->farmer();
        $cases = [
            ['photo' => null],
            ['sex' => 'cow'],
            ['breed_known' => 'yes', 'breed_description' => ''],
            ['age_basis' => 'exact', 'birth_date' => $this->day(1)],
            ['age_basis' => 'exact', 'birth_date' => '13/11/2026'],
            ['age_basis' => 'estimated', 'estimate_years' => ''],
            ['age_basis' => 'estimated', 'estimate_years' => '50', 'estimate_months' => '11'],
            ['age_basis' => 'estimated', 'estimate_years' => '2.5'],
            ['age_basis' => 'guess'],
            ['nickname' => str_repeat('আ', 81)],
            ['mother_details' => str_repeat('x', 301)],
            ['history_title' => 'Vaccine', 'history_details' => ''],
            ['history_title' => 'Vaccine given', 'history_details' => 'At the camp.', 'history_type' => 'vaccination', 'history_date_known' => 'yes', 'history_occurred_on' => $this->day(2)],
            ['history_title' => 'Before birth', 'history_details' => 'Impossible date.', 'history_type' => 'other', 'history_date_known' => 'yes', 'history_occurred_on' => $this->day(-900)],
            ['photo' => UploadedFile::fake()->createWithContent('cow.jpg', '<?php echo 1;')],
            ['photo' => UploadedFile::fake()->image('cow.gif')],
            ['submission_token' => 'nope'],
        ];

        // More than 10 posts in a minute would hit the rate limit (tested in CattleAccessTest), so switch it off here.
        $this->withoutMiddleware(ThrottleRequests::class);
        foreach ($cases as $override) {
            $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm($override))->assertSessionHasErrors();
        }

        $this->assertSame(0, Cattle::count());
        $this->assertSame(0, CattleHealthEntry::count());
        $this->assertSame([], Storage::disk('local')->allFiles(Cattle::PHOTO_DIRECTORY));
    }

    public function test_the_same_form_sent_twice_registers_once(): void
    {
        $farmer = $this->farmer();
        $form = $this->registerForm();

        $this->actingAs($farmer)->post(route('cattle.store'), $form);
        $form['photo'] = UploadedFile::fake()->image('again.jpg');
        $this->actingAs($farmer)->post(route('cattle.store'), $form)
            ->assertSessionHas('status', 'This animal was already registered from your earlier submission. Nothing was added twice.');

        $this->assertSame(1, Cattle::count());
        $this->assertCount(2, Storage::disk('local')->allFiles(Cattle::PHOTO_DIRECTORY));
    }

    public function test_farm_tags_are_unique_per_farmer_including_archived_cattle(): void
    {
        $farmer = $this->farmer();
        Cattle::factory()->for($farmer, 'farmer')->archived()->create(['farm_tag' => 'T-07']);

        $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm(['farm_tag' => 't-07']))
            ->assertSessionHasErrors('farm_tag'); // same tag, any letter case
        $this->actingAs($this->farmer())->post(route('cattle.store'), $this->registerForm(['farm_tag' => 'T-07']))
            ->assertSessionHasNoErrors(); // another farmer may use the same tag
        $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm(['farm_tag' => '']))->assertSessionHasNoErrors();
        $this->actingAs($farmer)->post(route('cattle.store'), $this->registerForm(['farm_tag' => '']))->assertSessionHasNoErrors();

        $this->assertSame(2, $farmer->cattle()->whereNull('farm_tag')->count(), 'many animals without a tag are fine');
    }

    public function test_a_database_failure_removes_the_new_photo(): void
    {
        Cattle::creating(fn () => throw new \RuntimeException('database unavailable'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->farmer())->post(route('cattle.store'), $this->registerForm());
            $this->fail('The failure should reach the caller.');
        } catch (\RuntimeException $e) {
            $this->assertSame('database unavailable', $e->getMessage());
        }

        $this->assertSame([], Storage::disk('local')->allFiles(Cattle::PHOTO_DIRECTORY));
    }
}
