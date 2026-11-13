<?php

namespace Tests\Feature\FarmJobs;

use App\Models\FarmJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** F20: a farmer posts, edits and closes their own jobs. Permissions are tested with direct requests. */
class FarmJobPostingTest extends TestCase
{
    use RefreshDatabase;

    private function farmer(): User
    {
        return User::factory()->withRoles(Role::FARMER)->create();
    }

    private function worker(): User
    {
        return User::factory()->withRoles(Role::WORKER)->create();
    }

    /** Today in Bangladesh plus $days, as YYYY-MM-DD. */
    private function day(int $days): string
    {
        return Carbon::parse(FarmJob::today())->addDays($days)->toDateString();
    }

    /** @return array<string, mixed> */
    private function validForm(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Harvest Aman rice, 3 bigha',
            'description' => 'Cut and bundle the rice. Bring your own sickle.',
            'work_type' => 'harvesting',
            'location' => 'Birulia, Savar',
            'work_date' => $this->day(3),
            'required_workers' => 8,
            'proposed_wage' => '700',
            'wage_basis' => 'daily',
            'unit_label' => '',
            'meal_details' => 'Lunch provided',
            'transport_details' => '',
        ], $overrides);
    }

    public function test_farmer_can_post_a_job_and_becomes_its_owner(): void
    {
        $farmer = $this->farmer();

        $response = $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm());

        $job = FarmJob::firstOrFail();
        $response->assertRedirect(route('farmer.jobs.show', $job));
        $this->assertSame($farmer->id, $job->farmer_id);
        $this->assertSame('open', $job->status);
        $this->assertSame('Harvest Aman rice, 3 bigha', $job->title);
        $this->assertSame($this->day(3), $job->work_date->toDateString());
        $this->assertSame('700.00', $job->proposed_wage);
        $this->assertNull($job->transport_details);
    }

    public function test_submitted_owner_and_status_are_ignored_when_posting(): void
    {
        $farmer = $this->farmer();
        $other = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm([
            'farmer_id' => $other->id,
            'status' => 'closed',
        ]));

        $job = FarmJob::firstOrFail();
        $this->assertSame($farmer->id, $job->farmer_id);
        $this->assertSame('open', $job->status);
    }

    public function test_worker_without_the_farmer_role_cannot_post_a_job(): void
    {
        $worker = $this->worker();

        $this->actingAs($worker)->get(route('farmer.jobs.create'))->assertForbidden();
        $this->actingAs($worker)->get(route('farmer.jobs.index'))->assertForbidden();
        $this->actingAs($worker)->post(route('farmer.jobs.store'), $this->validForm())->assertForbidden();

        $this->assertDatabaseCount('farm_jobs', 0);
    }

    public function test_account_with_farmer_and_worker_roles_can_post_and_browse(): void
    {
        $both = User::factory()->withRoles(Role::FARMER, Role::WORKER)->create();

        $this->actingAs($both)->post(route('farmer.jobs.store'), $this->validForm())->assertRedirect();
        $this->actingAs($both)->get(route('jobs.index'))->assertOk()->assertSee('Harvest Aman rice, 3 bigha');
    }

    public function test_guest_is_sent_to_login_for_posting_editing_and_closing(): void
    {
        $job = FarmJob::factory()->create(['title' => 'Original title']);

        $this->get(route('farmer.jobs.create'))->assertRedirect(route('login'));
        $this->post(route('farmer.jobs.store'), $this->validForm())->assertRedirect(route('login'));
        $this->get(route('farmer.jobs.edit', $job))->assertRedirect(route('login'));
        $this->put(route('farmer.jobs.update', $job), $this->validForm(['title' => 'Changed']))->assertRedirect(route('login'));
        $this->patch(route('farmer.jobs.close', $job))->assertRedirect(route('login'));

        $this->assertDatabaseCount('farm_jobs', 1);
        $this->assertSame('Original title', $job->fresh()->title);
        $this->assertSame('open', $job->fresh()->status);
    }

    public function test_required_fields_and_invalid_values_are_rejected(): void
    {
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.jobs.store'), [])
            ->assertSessionHasErrors(['title', 'description', 'work_type', 'location', 'work_date', 'required_workers', 'proposed_wage', 'wage_basis']);

        $invalid = [
            'title' => str_repeat('a', 151),
            'work_type' => 'dancing',
            'work_date' => 'next friday',
            'required_workers' => 0,
            'proposed_wage' => 0,
            'wage_basis' => 'hourly',
            'meal_details' => str_repeat('a', 1001),
        ];

        foreach ($invalid as $field => $value) {
            $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm([$field => $value]))
                ->assertSessionHasErrors($field);
        }

        // Does not fit decimal(10, 2), is negative, or has more than two decimal places.
        foreach (['100000000', '-50', '10.999'] as $wage) {
            $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm(['proposed_wage' => $wage]))
                ->assertSessionHasErrors('proposed_wage');
        }

        $this->assertDatabaseCount('farm_jobs', 0);
    }

    public function test_new_job_cannot_use_a_past_date_but_today_is_allowed(): void
    {
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm(['work_date' => $this->day(-1)]))
            ->assertSessionHasErrors('work_date');
        $this->assertDatabaseCount('farm_jobs', 0);

        $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm(['work_date' => $this->day(0)]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('farm_jobs', 1);
    }

    public function test_today_means_the_bangladesh_date_not_the_utc_date(): void
    {
        // 19:30 UTC on 20 Nov is already 01:30 on 21 Nov in Bangladesh.
        $this->travelTo(Carbon::parse('2026-11-20 19:30:00', 'UTC'));
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm(['work_date' => '2026-11-20']))
            ->assertSessionHasErrors('work_date');
        $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm(['work_date' => '2026-11-21']))
            ->assertSessionHasNoErrors();
    }

    public function test_unit_wage_needs_a_unit_label_and_other_wages_drop_it(): void
    {
        $farmer = $this->farmer();

        $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm(['wage_basis' => 'unit', 'unit_label' => '']))
            ->assertSessionHasErrors('unit_label');

        $this->actingAs($farmer)->post(route('farmer.jobs.store'), $this->validForm(['wage_basis' => 'unit', 'unit_label' => 'bag', 'proposed_wage' => '30']));
        $job = FarmJob::firstOrFail();
        $this->assertSame('bag', $job->unit_label);

        // Changing the basis away from "unit" clears the old label, even if the form still sends it.
        $this->actingAs($farmer)->put(route('farmer.jobs.update', $job), $this->validForm(['wage_basis' => 'daily', 'unit_label' => 'bag']));
        $this->assertNull($job->fresh()->unit_label);
        $this->assertSame('daily', $job->fresh()->wage_basis);
    }

    public function test_farmer_can_edit_their_own_job_without_changing_owner_or_status(): void
    {
        $farmer = $this->farmer();
        $other = $this->farmer();
        $job = FarmJob::factory()->for($farmer, 'farmer')->create();

        $this->actingAs($farmer)->get(route('farmer.jobs.edit', $job))->assertOk()->assertSee($job->title);

        $this->actingAs($farmer)->put(route('farmer.jobs.update', $job), $this->validForm([
            'title' => 'Harvest Aman rice, 5 bigha',
            'required_workers' => 12,
            'farmer_id' => $other->id,
            'status' => 'closed',
        ]))->assertRedirect(route('farmer.jobs.show', $job));

        $job->refresh();
        $this->assertSame('Harvest Aman rice, 5 bigha', $job->title);
        $this->assertSame(12, $job->required_workers);
        $this->assertSame($farmer->id, $job->farmer_id);
        $this->assertSame('open', $job->status);
    }

    public function test_another_farmer_cannot_edit_or_close_the_job(): void
    {
        $job = FarmJob::factory()->create(['title' => 'Original title']);
        $intruder = $this->farmer();

        $this->actingAs($intruder)->get(route('farmer.jobs.edit', $job))->assertForbidden();
        $this->actingAs($intruder)->put(route('farmer.jobs.update', $job), $this->validForm(['title' => 'Stolen']))->assertForbidden();
        $this->actingAs($intruder)->patch(route('farmer.jobs.close', $job))->assertForbidden();

        $this->assertSame('Original title', $job->fresh()->title);
        $this->assertSame('open', $job->fresh()->status);
    }

    public function test_past_job_keeps_its_date_when_edited_but_cannot_move_to_another_past_date(): void
    {
        $farmer = $this->farmer();
        $job = FarmJob::factory()->for($farmer, 'farmer')->past(3)->create();

        // Correcting the description while keeping the original past date is allowed.
        $this->actingAs($farmer)->put(route('farmer.jobs.update', $job), $this->validForm([
            'work_date' => $this->day(-3),
            'description' => 'Corrected description',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('Corrected description', $job->fresh()->description);

        // Moving it to a different past date is not.
        $this->actingAs($farmer)->put(route('farmer.jobs.update', $job), $this->validForm(['work_date' => $this->day(-1)]))
            ->assertSessionHasErrors('work_date');
        $this->assertSame($this->day(-3), $job->fresh()->work_date->toDateString());
    }

    public function test_closing_keeps_the_record_and_can_be_repeated_safely(): void
    {
        $farmer = $this->farmer();
        $job = FarmJob::factory()->for($farmer, 'farmer')->create();
        $before = $job->only(['title', 'description', 'work_date', 'proposed_wage', 'farmer_id']);

        $this->actingAs($farmer)->patch(route('farmer.jobs.close', $job))->assertRedirect(route('farmer.jobs.index'));
        $this->actingAs($farmer)->patch(route('farmer.jobs.close', $job))->assertRedirect(route('farmer.jobs.index'));

        $this->assertDatabaseCount('farm_jobs', 1);
        $job->refresh();
        $this->assertSame('closed', $job->status);
        $this->assertEquals($before, $job->only(array_keys($before)));
    }

    public function test_farmer_can_close_a_past_job(): void
    {
        $farmer = $this->farmer();
        $job = FarmJob::factory()->for($farmer, 'farmer')->past()->create();

        $this->actingAs($farmer)->patch(route('farmer.jobs.close', $job))->assertSessionHasNoErrors();

        $this->assertSame('closed', $job->fresh()->status);
    }

    public function test_my_posted_jobs_lists_only_own_jobs_including_closed_and_past(): void
    {
        $farmer = $this->farmer();
        FarmJob::factory()->for($farmer, 'farmer')->create(['title' => 'My open job']);
        FarmJob::factory()->for($farmer, 'farmer')->closed()->create(['title' => 'My closed job']);
        FarmJob::factory()->for($farmer, 'farmer')->past()->create(['title' => 'My past job']);
        FarmJob::factory()->create(['title' => 'Somebody else job']);

        $this->actingAs($farmer)->get(route('farmer.jobs.index'))
            ->assertOk()
            ->assertSee(['My open job', 'My closed job', 'My past job'])
            ->assertSee('Date passed')
            ->assertDontSee('Somebody else job');
    }

    public function test_there_is_no_route_to_delete_a_job(): void
    {
        $farmer = $this->farmer();
        $job = FarmJob::factory()->for($farmer, 'farmer')->create();

        $this->actingAs($farmer)->delete('/farmer/jobs/'.$job->id)->assertStatus(405);

        $this->assertDatabaseCount('farm_jobs', 1);
    }
}
