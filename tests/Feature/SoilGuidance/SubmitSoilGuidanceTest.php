<?php

namespace Tests\Feature\SoilGuidance;

use App\Models\SoilGuidanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** F15: the request form, its validation, and saving the observations and the optional photo. */
class SubmitSoilGuidanceTest extends TestCase
{
    use RefreshDatabase, SoilGuidanceHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSoilGuidance();
    }

    public function test_form_explains_the_feature_and_offers_not_sure(): void
    {
        $this->actingAs($this->farmer())->get(route('guidance.soil.create'))
            ->assertOk()
            ->assertSee('Describe the soil as you see it.')
            ->assertSee('Not sure')
            ->assertSee('A photo is optional. You can continue without one.')
            ->assertSee('will be sent to an AI service')
            ->assertDontSee('name="ph"', false) // no laboratory values are asked
            ->assertDontSee('Nitrogen');
    }

    public function test_farmer_submits_observations_without_a_photo_and_sees_the_guidance(): void
    {
        $this->fakeAiAnswer();
        $farmer = $this->farmer();

        $response = $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form());

        $record = SoilGuidanceRecord::sole();
        $response->assertRedirect(route('guidance.soil.show', $record));
        $this->assertSame(
            [$farmer->id, 'Boro rice', 'brown', 'moist', 'sticky', 'Water stands for two days after rain.', null],
            [$record->farmer_id, $record->crop_name, $record->soil_color, $record->moisture, $record->texture, $record->observations, $record->photo_path],
        );
        $this->assertSame([SoilGuidanceRecord::STATUS_COMPLETED, 1, null, 'gemini-test-model'], [$record->status, $record->attempt_count, $record->error_code, $record->ai_model]);
        $this->assertNotNull($record->completed_at);
        $this->assertEquals($this->guidance(), $record->guidance);

        // The no-photo request sends text only and tells the model that there is no photo.
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            $input = $request['input'];

            return count($input) === 1
                && $input[0]['type'] === 'text'
                && str_contains($input[0]['text'], 'no photo was supplied')
                && str_contains($input[0]['text'], '"crop": "Boro rice"')
                && str_contains($input[0]['text'], '"soil_texture": "Sticky"')
                && str_contains($input[0]['text'], 'Water stands for two days after rain.');
        });

        $this->actingAs($farmer)->get(route('guidance.soil.show', $record))
            ->assertOk()
            ->assertSee('Ready')
            ->assertSee('Summary of your observations')
            ->assertSee('You grow Boro rice in brown, moist, sticky soil')
            ->assertSee('General soil care')
            ->assertSee('Make small drains so extra water can leave the field.')
            ->assertSee('Fertilizer guidance')
            ->assertSee('Apply urea in two or three smaller splits')
            ->assertSee('What this guidance cannot tell you')
            ->assertSee('Only a soil test can show the real nutrient levels and pH.')
            ->assertSee('Questions that would help')
            ->assertSee('Which crop did you grow here last season?')
            ->assertSee('It does not replace a soil test or advice from a qualified agricultural professional.')
            ->assertSee('No photo was added.')
            ->assertDontSee('observations_summary'); // structured sections, never raw JSON
    }

    public function test_farmer_submits_with_a_photo_which_is_stored_privately_and_sent_inline(): void
    {
        $this->fakeAiAnswer();
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form([
            'photo' => UploadedFile::fake()->image('my field.jpg', 800, 600),
        ]))->assertRedirect();

        $record = SoilGuidanceRecord::sole();
        $this->assertSame(SoilGuidanceRecord::STATUS_COMPLETED, $record->status);

        // Private disk, own folder, a generated name (not the uploaded one), and exactly one file.
        $this->assertMatchesRegularExpression('#^soil-guidance-photos/[0-9a-f]{32}-display\.jpg$#', $record->photo_path);
        Storage::disk('local')->assertExists($record->photo_path);
        $this->assertSame([$record->photo_path], Storage::disk('local')->allFiles());

        // The provider gets the image inside the request (base64), after the text, and no file path or URL.
        Http::assertSent(function (Request $request) use ($record) {
            $input = $request['input'];
            $image = $input[1] ?? [];
            $bytes = base64_decode($image['data'] ?? '', true);

            return count($input) === 2
                && $input[0]['type'] === 'text'
                && str_contains($input[0]['text'], 'one photo of the soil is attached')
                && $image['type'] === 'image'
                && $image['mime_type'] === 'image/jpeg'
                && $bytes === Storage::disk('local')->get($record->photo_path)
                && ! isset($image['uri'])
                && ! str_contains($request->body(), $record->photo_path);
        });

        $this->actingAs($farmer)->get(route('guidance.soil.show', $record))
            ->assertOk()
            ->assertSee(route('guidance.soil.photo', $record))
            ->assertDontSee('No photo was added.');
    }

    public function test_owner_status_and_result_cannot_be_set_from_the_form(): void
    {
        $this->fakeAiAnswer();
        $farmer = $this->farmer();
        $other = $this->farmer();

        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form([
            'farmer_id' => $other->id,
            'status' => SoilGuidanceRecord::STATUS_COMPLETED,
            'attempt_count' => 99,
            'guidance' => ['observations_summary' => 'Made up by the form'],
            'photo_path' => '../../.env',
            'error_code' => 'hacked',
        ]))->assertRedirect();

        $record = SoilGuidanceRecord::sole();
        $this->assertSame([$farmer->id, 1, null, null], [$record->farmer_id, $record->attempt_count, $record->photo_path, $record->error_code]);
        $this->assertEquals($this->guidance(), $record->guidance, 'the result comes from the AI service, not the form');
    }

    public function test_ai_request_contains_no_account_details(): void
    {
        $this->fakeAiAnswer();
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form())->assertRedirect();

        Http::assertSent(fn (Request $request) => ! str_contains($request->body(), $farmer->name)
            && ! str_contains($request->body(), $farmer->phone));
    }

    public function test_required_fields_are_checked_and_typed_answers_are_kept(): void
    {
        $farmer = $this->farmer();

        $this->actingAs($farmer)->from(route('guidance.soil.create'))
            ->post(route('guidance.soil.store'), ['submission_token' => $this->form()['submission_token'], 'observations' => 'Hard when dry.'])
            ->assertRedirect(route('guidance.soil.create'));

        // The form comes back with the typed text, and one simple message beside each of the four empty fields.
        $this->actingAs($farmer)->get(route('guidance.soil.create'))
            ->assertSee('Hard when dry.')
            ->assertSee('id="field-crop_name-error"', false)
            ->assertSee('Write the name of the crop you grow.')
            ->assertSee('id="field-soil_color-error"', false)
            ->assertSee('id="field-moisture-error"', false)
            ->assertSee('id="field-texture-error"', false)
            ->assertDontSee('id="field-observations-error"', false)
            ->assertDontSee('id="field-photo-error"', false)
            ->assertSee('If you chose a photo, please choose it again.');

        $this->assertSame(0, SoilGuidanceRecord::count());
        Http::assertNothingSent();
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function invalidInputs(): array
    {
        return [
            'crop name too long' => [['crop_name' => str_repeat('a', 101)], 'crop_name'],
            'crop name is a list' => [['crop_name' => ['rice']], 'crop_name'],
            'unknown soil colour' => [['soil_color' => 'purple'], 'soil_color'],
            'colour label instead of code' => [['soil_color' => 'Brown'], 'soil_color'],
            'unknown moisture' => [['moisture' => 'soaked'], 'moisture'],
            'unknown texture' => [['texture' => 'rocky'], 'texture'],
            'notes too long' => [['observations' => str_repeat('a', 2001)], 'observations'],
            'missing form token' => [['submission_token' => ''], 'submission_token'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_values_are_rejected(array $overrides, string $field): void
    {
        $this->actingAs($this->farmer())->post(route('guidance.soil.store'), $this->form($overrides))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, SoilGuidanceRecord::count());
        Http::assertNothingSent();
    }

    public function test_not_sure_is_accepted_for_every_observation_and_stays_unknown_for_the_ai(): void
    {
        $this->fakeAiAnswer();

        $this->actingAs($this->farmer())->post(route('guidance.soil.store'), $this->form([
            'soil_color' => 'not_sure', 'moisture' => 'not_sure', 'texture' => 'not_sure', 'observations' => '',
        ]))->assertSessionHasNoErrors();

        $record = SoilGuidanceRecord::sole();
        $this->assertSame(['not_sure', 'not_sure', 'not_sure', null], [$record->soil_color, $record->moisture, $record->texture, $record->observations]);

        Http::assertSent(fn (Request $request) => str_contains($request['input'][0]['text'], '"soil_colour": "Not sure"')
            && str_contains($request['input'][0]['text'], '"soil_moisture": "Not sure"')
            && str_contains($request['input'][0]['text'], '"soil_texture": "Not sure"')
            && str_contains($request['system_instruction'], 'Do not guess a value for it.'));
    }

    /** @return array<string, array{0: callable(): UploadedFile}> */
    public static function badPhotos(): array
    {
        return [
            'svg with a script' => [fn () => UploadedFile::fake()->createWithContent('soil.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
            'php code named .jpg' => [fn () => UploadedFile::fake()->createWithContent('soil.jpg', '<?php echo "run me"; ?>')],
            'gif image' => [fn () => UploadedFile::fake()->image('soil.gif', 100, 100)],
            'pdf document' => [fn () => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf')],
            'larger than 5 MB' => [fn () => UploadedFile::fake()->image('soil.jpg', 100, 100)->size(5121)],
            'wider than 6000 pixels' => [fn () => UploadedFile::fake()->image('soil.png', 6001, 10)],
        ];
    }

    #[DataProvider('badPhotos')]
    public function test_unsupported_and_oversized_photos_are_rejected(callable $photo): void
    {
        $this->actingAs($this->farmer())->post(route('guidance.soil.store'), $this->form(['photo' => $photo()]))
            ->assertSessionHasErrors('photo');

        $this->assertSame(0, SoilGuidanceRecord::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertNothingSent();
    }

    public function test_two_photos_are_not_accepted(): void
    {
        $this->actingAs($this->farmer())->post(route('guidance.soil.store'), $this->form([
            'photo' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]))->assertSessionHasErrors('photo');

        $this->assertSame(0, SoilGuidanceRecord::count());
    }

    public function test_sending_the_same_form_twice_makes_one_request_and_one_ai_call(): void
    {
        $this->fakeAiAnswer();
        $farmer = $this->farmer();
        $form = $this->form();

        $this->actingAs($farmer)->post(route('guidance.soil.store'), $form)->assertRedirect();
        $record = SoilGuidanceRecord::sole();
        $this->actingAs($farmer)->post(route('guidance.soil.store'), $form)->assertRedirect(route('guidance.soil.show', $record));

        $this->assertSame(1, SoilGuidanceRecord::count());
        Http::assertSentCount(1);
    }

    public function test_guidance_is_requested_in_the_page_language(): void
    {
        $this->fakeAiAnswer();
        $farmer = $this->farmer();
        $farmer->update(['locale' => 'bn']);

        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form())->assertRedirect();

        $this->assertSame('bn', SoilGuidanceRecord::sole()->locale);
        Http::assertSent(fn (Request $request) => str_contains($request['system_instruction'], 'Write every text value in simple Bangla'));
    }

    public function test_creating_requests_is_rate_limited_per_account(): void
    {
        $this->fakeAiAnswer();
        $farmer = $this->farmer();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form())->assertRedirect();
        }
        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form())->assertTooManyRequests();

        $this->assertSame(5, SoilGuidanceRecord::count());
        Http::assertSentCount(5);

        // The limit is per account: another farmer is not blocked.
        $this->actingAs($this->farmer())->post(route('guidance.soil.store'), $this->form())->assertRedirect();
    }
}
