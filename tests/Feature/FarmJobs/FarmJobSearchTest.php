<?php

namespace Tests\Feature\FarmJobs;

use App\Models\FarmJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** F20: browsing, searching and opening jobs. Only open jobs dated today or later are "available". */
class FarmJobSearchTest extends TestCase
{
    use RefreshDatabase;

    private function worker(): User
    {
        return User::factory()->withRoles(Role::WORKER)->create();
    }

    private function search(array $filters = [])
    {
        return $this->get(route('jobs.index', $filters));
    }

    public function test_job_list_shows_open_future_jobs_to_guests_and_workers(): void
    {
        FarmJob::factory()->create(['title' => 'Plant Boro seedlings', 'location' => 'Shibganj, Bogura', 'proposed_wage' => 650]);

        $this->search()->assertOk()->assertSee(['Plant Boro seedlings', 'Shibganj, Bogura', '650', 'per worker per day']);
        $this->actingAs($this->worker())->get(route('jobs.index'))->assertOk()->assertSee('Plant Boro seedlings');
    }

    public function test_closed_and_past_jobs_are_not_listed_and_today_is(): void
    {
        FarmJob::factory()->create(['title' => 'Job for today', 'work_date' => FarmJob::today()]);
        FarmJob::factory()->closed()->create(['title' => 'Closed job']);
        FarmJob::factory()->past(1)->create(['title' => 'Yesterday job']);

        $this->search()->assertOk()
            ->assertSee('Job for today')
            ->assertDontSee('Closed job')
            ->assertDontSee('Yesterday job');
    }

    public function test_closing_a_job_removes_it_from_search_but_not_from_the_owners_list(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();
        $job = FarmJob::factory()->for($farmer, 'farmer')->create(['title' => 'Weed the brinjal field']);

        $this->search()->assertSee('Weed the brinjal field');

        $this->actingAs($farmer)->patch(route('farmer.jobs.close', $job));

        $this->actingAs($this->worker())->get(route('jobs.index'))->assertDontSee('Weed the brinjal field');
        $this->actingAs($farmer)->get(route('farmer.jobs.index'))->assertSee('Weed the brinjal field')->assertSee('Closed');
    }

    public function test_job_disappears_when_the_bangladesh_date_passes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 12:00:00', 'UTC'));
        FarmJob::factory()->create(['title' => 'Friday harvest', 'work_date' => '2026-11-20']);

        $this->search()->assertSee('Friday harvest');

        // 18:30 UTC is 00:30 the next day in Bangladesh.
        $this->travelTo(Carbon::parse('2026-11-20 18:30:00', 'UTC'));

        $this->search()->assertDontSee('Friday harvest');
    }

    public function test_keyword_matches_title_or_description(): void
    {
        FarmJob::factory()->create(['title' => 'Harvest mustard', 'description' => 'Two days of work']);
        FarmJob::factory()->create(['title' => 'Field helper', 'description' => 'Help carry mustard to the store']);
        FarmJob::factory()->create(['title' => 'Spray mango trees', 'description' => 'Sprayer provided']);

        $this->search(['q' => 'mustard'])
            ->assertSee('Harvest mustard')
            ->assertSee('Field helper')
            ->assertDontSee('Spray mango trees');
    }

    public function test_keyword_cannot_expose_a_closed_or_past_job(): void
    {
        FarmJob::factory()->create(['title' => 'Open mustard job']);
        FarmJob::factory()->closed()->create(['title' => 'Closed mustard job']);
        FarmJob::factory()->past()->create(['title' => 'Past job', 'description' => 'Old mustard job']);

        $this->search(['q' => 'mustard'])
            ->assertSee('Open mustard job')
            ->assertDontSee('Closed mustard job')
            ->assertDontSee('Past job');
    }

    public function test_filters_work_together_with_the_keyword(): void
    {
        $match = ['location' => 'Savar, Dhaka', 'work_type' => 'harvesting', 'wage_basis' => 'daily', 'proposed_wage' => 700,
            'work_date' => Carbon::parse(FarmJob::today())->addDays(2)];

        FarmJob::factory()->create(['title' => 'Rice harvest A (match)'] + $match);
        FarmJob::factory()->create(['title' => 'Rice harvest B (other place)', 'location' => 'Bogura'] + $match);
        FarmJob::factory()->create(['title' => 'Rice harvest C (other type)', 'work_type' => 'planting'] + $match);
        FarmJob::factory()->create(['title' => 'Rice harvest D (low wage)', 'proposed_wage' => 500] + $match);
        FarmJob::factory()->create(['title' => 'Rice harvest E (task wage)', 'wage_basis' => 'task'] + $match);
        FarmJob::factory()->create(['title' => 'Rice harvest F (other day)', 'work_date' => Carbon::parse(FarmJob::today())->addDays(5)] + $match);
        FarmJob::factory()->create(['title' => 'Jute cutting G (no keyword)', 'description' => 'Cut jute'] + $match);

        $response = $this->search([
            'q' => 'rice',
            'location' => 'savar',
            'work_type' => 'harvesting',
            'work_date' => $match['work_date']->toDateString(),
            'wage_basis' => 'daily',
            'min_wage' => 600,
        ]);

        $response->assertOk()->assertSee('Rice harvest A (match)');
        foreach (['B (other place)', 'C (other type)', 'D (low wage)', 'E (task wage)', 'F (other day)', 'G (no keyword)'] as $other) {
            $response->assertDontSee($other);
        }
    }

    public function test_percent_sign_in_a_search_is_not_a_wildcard(): void
    {
        FarmJob::factory()->create(['title' => 'Ordinary job']);

        $this->search(['q' => '%'])->assertOk()->assertDontSee('Ordinary job');
    }

    public function test_minimum_wage_needs_a_wage_basis(): void
    {
        FarmJob::factory()->create(['title' => 'Daily job', 'proposed_wage' => 800]);

        $this->search(['min_wage' => 600])
            ->assertRedirect(route('jobs.index'))
            ->assertSessionHasErrors('wage_basis');

        $this->search(['min_wage' => 600, 'wage_basis' => 'daily'])->assertOk()->assertSee('Daily job');
    }

    public function test_invalid_filters_give_an_error_message_not_a_server_error(): void
    {
        $this->search(['work_date' => 'not-a-date'])->assertRedirect(route('jobs.index'))->assertSessionHasErrors('work_date');
        $this->search(['work_type' => 'dancing'])->assertRedirect(route('jobs.index'))->assertSessionHasErrors('work_type');
        $this->search(['wage_basis' => 'hourly'])->assertRedirect(route('jobs.index'))->assertSessionHasErrors('wage_basis');
        $this->search(['min_wage' => 'lots', 'wage_basis' => 'daily'])->assertRedirect(route('jobs.index'))->assertSessionHasErrors('min_wage');
    }

    public function test_jobs_are_paginated_by_ten_soonest_first_and_page_links_keep_the_filters(): void
    {
        $base = Carbon::parse(FarmJob::today());
        // Created in reverse so the order must come from the work date, not from the id.
        foreach (range(12, 1) as $n) {
            FarmJob::factory()->create([
                'title' => sprintf('Paddy job number %02d', $n),
                'location' => 'Savar',
                'work_date' => $base->copy()->addDays($n),
            ]);
        }
        FarmJob::factory()->create(['title' => 'Elsewhere job', 'location' => 'Bogura']);

        $pageOne = $this->search(['location' => 'Savar']);
        $pageOne->assertOk()
            ->assertSeeInOrder(['Paddy job number 01', 'Paddy job number 02', 'Paddy job number 10'])
            ->assertDontSee('Paddy job number 11')
            ->assertDontSee('Elsewhere job')
            ->assertSee('location=Savar&amp;page=2', false);

        $this->search(['location' => 'Savar', 'page' => 2])
            ->assertOk()
            ->assertSee(['Paddy job number 11', 'Paddy job number 12'])
            ->assertDontSee('Paddy job number 10');
    }

    public function test_empty_search_shows_a_helpful_message(): void
    {
        FarmJob::factory()->create(['location' => 'Savar']);

        $this->search(['location' => 'Sylhet'])->assertOk()->assertSee('No jobs match your search');
    }

    public function test_details_page_shows_the_job_and_only_the_farmers_name(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create(['name' => 'Karim Mia', 'phone' => '01712345678']);
        $job = FarmJob::factory()->for($farmer, 'farmer')->unitBased('bag')->create([
            'title' => 'Carry paddy bags',
            'proposed_wage' => 30,
            'meal_details' => null,
        ]);

        $this->actingAs($this->worker())->get(route('jobs.show', $job))
            ->assertOk()
            ->assertSee(['Carry paddy bags', 'Karim Mia', 'per worker per bag', 'Not specified'])
            ->assertDontSee('01712345678')
            ->assertDontSee('Close job')
            ->assertDontSee(route('farmer.jobs.edit', $job));
    }

    public function test_closed_and_past_jobs_cannot_be_opened_by_other_people(): void
    {
        $closed = FarmJob::factory()->closed()->create();
        $past = FarmJob::factory()->past()->create();

        foreach ([$closed, $past] as $job) {
            $this->get(route('jobs.show', $job))->assertNotFound();
            $this->actingAs($this->worker())->get(route('jobs.show', $job))->assertNotFound();
            auth()->logout();
        }

        $this->get(route('jobs.show', 999999))->assertNotFound();
    }

    public function test_owner_can_still_open_their_closed_and_past_jobs(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();
        $closed = FarmJob::factory()->for($farmer, 'farmer')->closed()->create(['title' => 'My closed job']);
        $past = FarmJob::factory()->for($farmer, 'farmer')->past()->create(['title' => 'My past job']);

        $this->actingAs($farmer)->get(route('jobs.show', $closed))->assertOk()->assertSee('My closed job')->assertDontSee('Close job');
        $this->actingAs($farmer)->get(route('farmer.jobs.show', $past))->assertOk()->assertSee('My past job');
    }

    public function test_text_typed_by_a_farmer_is_escaped(): void
    {
        $job = FarmJob::factory()->create(['title' => '<script>alert(1)</script>', 'description' => '<b>bold</b>']);

        $this->get(route('jobs.show', $job))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<b>bold</b>', false);
    }
}
