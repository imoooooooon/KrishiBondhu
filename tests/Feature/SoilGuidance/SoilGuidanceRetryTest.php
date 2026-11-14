<?php

namespace Tests\Feature\SoilGuidance;

use App\Exceptions\AiGuidanceException;
use App\Models\SoilGuidanceRecord;
use App\Services\SoilGuidanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** F15: trying a failed request again, the limits on that, and protection against processing a record twice. */
class SoilGuidanceRetryTest extends TestCase
{
    use RefreshDatabase, SoilGuidanceHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSoilGuidance();
        config(['soil_guidance.max_attempts' => 3, 'soil_guidance.retry_cooldown_seconds' => 30, 'soil_guidance.stale_after_seconds' => 120]);
    }

    public function test_a_failed_request_is_retried_with_the_same_saved_observations(): void
    {
        // First the provider is down, then it answers.
        Http::fake([self::AI_URL => Http::sequence()
            ->push(['error' => ['status' => 'UNAVAILABLE', 'message' => 'Overloaded.']], 503)
            ->push($this->interaction(json_encode($this->guidance())))]);
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form(['crop_name' => 'Potato', 'observations' => 'White crust on top.']));
        $record = SoilGuidanceRecord::sole();
        $this->assertSame([SoilGuidanceRecord::STATUS_FAILED, AiGuidanceException::PROVIDER_ERROR, 1], [$record->status, $record->error_code, $record->attempt_count]);

        $this->travel(31)->seconds(); // the cooldown passes

        // The retry sends no observations at all: even if a form tried to, they would be ignored.
        $this->actingAs($farmer)->post(route('guidance.soil.retry', $record), ['crop_name' => 'Changed crop', 'observations' => 'Changed note'])
            ->assertRedirect(route('guidance.soil.show', $record))
            ->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame([SoilGuidanceRecord::STATUS_COMPLETED, null, 2], [$record->status, $record->error_code, $record->attempt_count]);
        $this->assertSame(['Potato', 'White crust on top.'], [$record->crop_name, $record->observations]);
        $this->assertSame(1, SoilGuidanceRecord::count(), 'a retry never creates a second request');

        Http::assertSentCount(2);
        $prompts = Http::recorded()->map(fn (array $pair) => $pair[0]['input'][0]['text']);
        $this->assertSame($prompts[0], $prompts[1], 'both attempts sent exactly the same observations');
        $this->assertStringContainsString('"crop": "Potato"', $prompts[1]);
        $this->assertStringNotContainsString('Changed', $prompts[1]);

        $this->actingAs($farmer)->get(route('guidance.soil.show', $record))->assertSee('Summary of your observations')->assertDontSee('Try again');
    }

    public function test_a_completed_request_cannot_be_retried(): void
    {
        $record = SoilGuidanceRecord::factory()->completed()->create();
        $before = $record->guidance;

        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))
            ->assertRedirect(route('guidance.soil.show', $record))
            ->assertSessionHasErrors('retry');

        $record->refresh();
        $this->assertSame([SoilGuidanceRecord::STATUS_COMPLETED, 1], [$record->status, $record->attempt_count]);
        $this->assertEquals($before, $record->guidance);
        Http::assertNothingSent();
    }

    public function test_a_request_that_is_being_processed_cannot_be_retried(): void
    {
        $record = SoilGuidanceRecord::factory()->processing()->create();

        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertSessionHasErrors('retry');

        $this->assertSame([SoilGuidanceRecord::STATUS_PROCESSING, 1], [$record->fresh()->status, $record->fresh()->attempt_count]);
        Http::assertNothingSent();

        // Its page asks the farmer to wait, reloads by itself, and offers no retry button.
        $this->actingAs($record->farmer)->get(route('guidance.soil.show', $record))
            ->assertOk()
            ->assertSee('In progress')
            ->assertSee('Preparing your guidance')
            ->assertSee('http-equiv="refresh"', false)
            ->assertDontSee('Try again');
    }

    public function test_retry_has_a_cooldown(): void
    {
        $this->fakeAiAnswer();
        $record = SoilGuidanceRecord::factory()->failed()->create(['last_attempt_at' => now()->subSeconds(10)]);

        // Too early: the farmer is sent back to the result page, which says how long to wait.
        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertRedirect(route('guidance.soil.show', $record));
        $this->actingAs($record->farmer)->get(route('guidance.soil.show', $record))->assertSee('seconds before trying again.');
        $this->assertSame([SoilGuidanceRecord::STATUS_FAILED, 1], [$record->fresh()->status, $record->fresh()->attempt_count]);
        Http::assertNothingSent();

        $this->travel(25)->seconds();
        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertSessionHasNoErrors();
        $this->assertSame(SoilGuidanceRecord::STATUS_COMPLETED, $record->fresh()->status);
    }

    public function test_retries_stop_at_the_attempt_limit(): void
    {
        Http::fake([self::AI_URL => Http::response(['error' => ['status' => 'INTERNAL', 'message' => 'Broken.']], 500)]);
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('guidance.soil.store'), $this->form()); // attempt 1
        $record = SoilGuidanceRecord::sole();

        foreach ([2, 3] as $attempt) {
            $this->travel(31)->seconds();
            $this->actingAs($farmer)->post(route('guidance.soil.retry', $record))->assertSessionHasNoErrors();
            $this->assertSame([SoilGuidanceRecord::STATUS_FAILED, $attempt], [$record->fresh()->status, $record->fresh()->attempt_count]);
        }

        // A fourth attempt is refused: the limit is 3.
        $this->travel(31)->seconds();
        $this->actingAs($farmer)->post(route('guidance.soil.retry', $record))->assertSessionHasErrors('retry');
        $this->assertSame(3, $record->fresh()->attempt_count);
        Http::assertSentCount(3);

        $this->actingAs($farmer)->get(route('guidance.soil.show', $record))
            ->assertSee('No more tries are left for this request.')
            ->assertDontSee('Try again')
            ->assertSee(route('guidance.soil.create'));
    }

    public function test_the_attempt_limit_can_be_configured(): void
    {
        config(['soil_guidance.max_attempts' => 1]);
        $record = SoilGuidanceRecord::factory()->failed()->create();

        $this->assertFalse($record->canRetry());
        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertSessionHasErrors('retry');
        Http::assertNothingSent();
    }

    public function test_retrying_is_rate_limited_per_account(): void
    {
        $record = SoilGuidanceRecord::factory()->completed()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertRedirect();
        }
        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertTooManyRequests();
    }

    public function test_a_record_can_only_be_claimed_once(): void
    {
        $this->fakeAiAnswer();
        $service = app(SoilGuidanceService::class);
        $record = SoilGuidanceRecord::factory()->create(); // pending

        // Two requests loaded the same pending record. The first one claims it and finishes.
        $secondCopy = SoilGuidanceRecord::find($record->id);
        $this->assertTrue($service->process($record));
        $this->assertSame(SoilGuidanceRecord::STATUS_COMPLETED, $record->status);

        // The second request still holds the old "pending" copy, but the database refuses the claim.
        $this->assertFalse($service->process($secondCopy));

        Http::assertSentCount(1);
        $this->assertSame(1, $record->fresh()->attempt_count);
    }

    public function test_a_record_that_another_request_is_processing_is_left_alone(): void
    {
        $record = SoilGuidanceRecord::factory()->processing()->create();

        $this->assertFalse(app(SoilGuidanceService::class)->process($record));

        $this->assertSame([SoilGuidanceRecord::STATUS_PROCESSING, 1], [$record->fresh()->status, $record->fresh()->attempt_count]);
        Http::assertNothingSent();
    }

    public function test_a_late_answer_from_an_old_attempt_cannot_overwrite_a_newer_attempt(): void
    {
        // Attempt 1 got stuck and was claimed again as attempt 2. When attempt 1's answer finally arrives,
        // the record is already on attempt 2, so the old attempt changes nothing.
        $record = SoilGuidanceRecord::factory()->processing()->create(['attempt_count' => 2]);
        $oldAttempt = SoilGuidanceRecord::find($record->id);
        $oldAttempt->attempt_count = 1;

        $finish = new \ReflectionMethod(SoilGuidanceService::class, 'finish');
        $finish->invoke(app(SoilGuidanceService::class), $oldAttempt, ['status' => SoilGuidanceRecord::STATUS_FAILED, 'error_code' => 'timeout']);

        $this->assertSame([SoilGuidanceRecord::STATUS_PROCESSING, null], [$record->fresh()->status, $record->fresh()->error_code]);
    }

    public function test_a_request_stuck_in_processing_can_be_tried_again_later(): void
    {
        $this->fakeAiAnswer();
        $record = SoilGuidanceRecord::factory()->processing()->create(['last_attempt_at' => now()->subMinutes(5)]);

        $this->actingAs($record->farmer)->get(route('guidance.soil.show', $record))
            ->assertSee('Failed')
            ->assertSee('The last attempt did not finish.')
            ->assertSee('Try again')
            ->assertDontSee('http-equiv="refresh"', false);

        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertSessionHasNoErrors();

        $this->assertSame([SoilGuidanceRecord::STATUS_COMPLETED, 2], [$record->fresh()->status, $record->fresh()->attempt_count]);
    }

    public function test_a_saved_request_that_was_never_sent_can_be_started_from_its_page(): void
    {
        $this->fakeAiAnswer();
        $record = SoilGuidanceRecord::factory()->create(); // pending: saved, but the first attempt never ran

        $this->actingAs($record->farmer)->get(route('guidance.soil.show', $record))
            ->assertSee('Waiting')
            ->assertSee('Guidance has not been generated yet')
            ->assertSee('Try again');

        $this->actingAs($record->farmer)->post(route('guidance.soil.retry', $record))->assertSessionHasNoErrors();

        $this->assertSame(SoilGuidanceRecord::STATUS_COMPLETED, $record->fresh()->status);
        Http::assertSent(fn (Request $request) => str_contains($request['input'][0]['text'], $record->crop_name));
    }
}
