<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\Role;
use App\Models\TreatmentRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TreatmentHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => Role::FARMER], ['name' => 'Farmer']);
        Role::firstOrCreate(['slug' => Role::BUYER], ['name' => 'Buyer']);
    }

    public function test_guests_and_non_farmers_cannot_access_treatments(): void
    {
        $this->get(route('treatments.index'))->assertRedirect(route('login'));
        $this->get(route('reminders.index'))->assertRedirect(route('login'));

        $buyer = User::factory()->withRoles(Role::BUYER)->create();

        $this->actingAs($buyer)->get(route('treatments.index'))->assertForbidden();
        $this->actingAs($buyer)->get(route('treatments.create'))->assertForbidden();
        $this->actingAs($buyer)->post(route('treatments.store'), [])->assertForbidden();
    }

    public function test_farmer_can_create_treatment_record_and_auto_creates_reminder(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();

        // ভ্যালিডেশন চেক (follow_up_date < treated_on এবং অবৈধ ক্যাটাগরি)
        $this->actingAs($farmer)
            ->post(route('treatments.store'), [
                'category' => 'invalid',
                'subject_name' => '',
                'treated_on' => '2026-11-15',
                'follow_up_date' => '2026-11-10',
                'status' => 'ongoing',
            ])
            ->assertSessionHasErrors(['category', 'subject_name', 'problem', 'treatment_notes', 'follow_up_date']);

        $response = $this->actingAs($farmer)
            ->post(route('treatments.store'), [
                'category' => TreatmentRecord::CATEGORY_ANIMAL,
                'subject_name' => 'Raja (Bull)',
                'problem' => 'Mild fever and cough',
                'treatment_notes' => 'Given prescribed antipyretic for 3 days',
                'treated_on' => '2026-11-14',
                'follow_up_date' => '2026-11-20',
                'status' => TreatmentRecord::STATUS_ONGOING,
            ]);

        $record = TreatmentRecord::firstOrFail();
        $response->assertRedirect(route('treatments.show', $record));

        $this->assertDatabaseHas('treatment_records', [
            'id' => $record->id,
            'farmer_id' => $farmer->id,
            'subject_name' => 'Raja (Bull)',
        ]);

        $this->assertDatabaseHas('reminders', [
            'user_id' => $farmer->id,
            'source_type' => TreatmentRecord::class,
            'source_id' => $record->id,
            'completed_at' => null,
        ]);
    }

    public function test_farmer_can_update_and_delete_own_record_and_sync_reminder(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();

        $this->actingAs($farmer)->post(route('treatments.store'), [
            'category' => TreatmentRecord::CATEGORY_CROP,
            'subject_name' => 'BRRI Dhan 28',
            'problem' => 'Leaf blast spots',
            'treatment_notes' => 'Sprayed fungicide first dose',
            'treated_on' => '2026-11-14',
            'follow_up_date' => '2026-11-21',
            'status' => TreatmentRecord::STATUS_ONGOING,
        ]);

        $record = TreatmentRecord::firstOrFail();

        // স্ট্যাটাস completed করলে পেন্ডিং রিমাইন্ডার সরে যাবে
        $this->actingAs($farmer)
            ->patch(route('treatments.update', $record), [
                'category' => TreatmentRecord::CATEGORY_CROP,
                'subject_name' => 'BRRI Dhan 28',
                'problem' => 'Leaf blast spots',
                'treatment_notes' => 'Recovered after second spray',
                'treated_on' => '2026-11-14',
                'follow_up_date' => '2026-11-21',
                'status' => TreatmentRecord::STATUS_COMPLETED,
            ])
            ->assertRedirect(route('treatments.show', $record));

        $this->assertDatabaseCount('reminders', 0);

        $this->actingAs($farmer)
            ->delete(route('treatments.destroy', $record))
            ->assertRedirect(route('treatments.index'));

        $this->assertDatabaseCount('treatment_records', 0);
    }

    public function test_other_farmer_cannot_view_edit_or_delete_record(): void
    {
        $owner = User::factory()->withRoles(Role::FARMER)->create();
        $intruder = User::factory()->withRoles(Role::FARMER)->create();

        $record = new TreatmentRecord([
            'category' => TreatmentRecord::CATEGORY_CROP,
            'subject_name' => 'Potato Plot A',
            'problem' => 'Early blight',
            'treatment_notes' => 'Applied copper fungicide',
            'treated_on' => '2026-11-14',
            'status' => TreatmentRecord::STATUS_ONGOING,
        ]);
        $record->farmer_id = $owner->id;
        $record->save();

        $this->actingAs($intruder)->get(route('treatments.show', $record))->assertForbidden();
        $this->actingAs($intruder)->get(route('treatments.edit', $record))->assertForbidden();
        $this->actingAs($intruder)->patch(route('treatments.update', $record), [
            'category' => TreatmentRecord::CATEGORY_CROP,
            'subject_name' => 'Hacked',
            'problem' => 'Hacked',
            'treatment_notes' => 'Hacked',
            'treated_on' => '2026-11-14',
            'status' => TreatmentRecord::STATUS_ONGOING,
        ])->assertForbidden();
        $this->actingAs($intruder)->delete(route('treatments.destroy', $record))->assertForbidden();
    }

    public function test_user_can_view_and_complete_own_reminder_only(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create();
        $otherUser = User::factory()->withRoles(Role::FARMER)->create();

        $reminder = new Reminder([
            'title' => 'Follow-up: Cow checkup',
            'notes' => 'Check temperature',
            'remind_on' => '2026-11-18',
        ]);
        $reminder->user_id = $farmer->id;
        $reminder->save();

        $this->actingAs($farmer)
            ->get(route('reminders.index'))
            ->assertOk()
            ->assertSee('Follow-up: Cow checkup');

        $this->actingAs($otherUser)
            ->patch(route('reminders.complete', $reminder))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->patch(route('reminders.complete', $reminder))
            ->assertRedirect(route('reminders.index'));

        $this->assertNotNull($reminder->fresh()->completed_at);
    }

    public function test_treatment_and_reminder_pages_render_in_bangla(): void
    {
        $farmer = User::factory()->withRoles(Role::FARMER)->create(['locale' => 'bn']);

        $this->actingAs($farmer)
            ->get(route('treatments.index'))
            ->assertOk()
            ->assertSee('চিকিৎসার ইতিহাস');

        $this->actingAs($farmer)
            ->get(route('reminders.index'))
            ->assertOk()
            ->assertSee('ফলো-আপ রিমাইন্ডার');
    }
}
