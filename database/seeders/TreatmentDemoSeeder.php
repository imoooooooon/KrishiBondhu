<?php

namespace Database\Seeders;

use App\Models\Reminder;
use App\Models\Role;
use App\Models\TreatmentRecord;
use Illuminate\Database\Seeder;

/**
 * Demo data for F19 (treatment history and reminders), for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=TreatmentDemoSeeder
 *
 * Gives the demo farmers crop and animal treatment records. An ongoing record with a follow-up date gets an in-site
 * reminder, exactly as TreatmentController::syncReminder makes one: some are due soon, one is overdue, one is done.
 * The notes are fictional examples, not veterinary or agronomic advice. Password for every account: "password".
 * Safe to run twice: nothing is added if a demo farmer already has a treatment record.
 */
class TreatmentDemoSeeder extends Seeder
{
    use DemoAccounts;

    public function run(): void
    {
        if ($this->refuseInProduction()) {
            return;
        }

        $karim = $this->demoUser('01700000001', 'Karim Mia (demo farmer)', [Role::FARMER]);
        $salma = $this->demoUser('01700000002', 'Salma Khatun (demo farmer and worker)', [Role::FARMER, Role::WORKER]);

        if (TreatmentRecord::whereIn('farmer_id', [$karim->id, $salma->id])->exists()) {
            $this->command?->info('Demo treatment records already exist. Nothing was added.');

            return;
        }

        $crop = TreatmentRecord::CATEGORY_CROP;
        $animal = TreatmentRecord::CATEGORY_ANIMAL;
        $ongoing = TreatmentRecord::STATUS_ONGOING;
        $completed = TreatmentRecord::STATUS_COMPLETED;

        // [farmer, category, subject, treated (days ago), follow-up (days from today or null), status, problem, notes, reminder done?]
        $records = [
            [$karim, $animal, 'Cow Moni (T-01)', 2, 3, $ongoing, 'Eating less and giving less milk for two days.',
                'Vet advised more green grass and clean water, and to watch her for fever. Check again in five days.', false],
            [$karim, $animal, 'Goats (2 kids)', 3, 1, $ongoing, 'Coughing and runny nose.',
                'Kept apart from the herd in a dry shed. Vet visit booked for tomorrow.', false],
            [$karim, $crop, 'Brinjal, 1 bigha (Dhamrai)', 9, -2, $ongoing, 'Shoot and fruit borer: holes in shoots and young fruit.',
                'Removed and buried the damaged shoots. Put up pheromone traps as the agriculture officer suggested.', false],
            [$karim, $crop, 'Aman paddy, 3 bigha', 40, -25, $completed, 'Yellow leaves in patches after heavy rain.',
                'Drained standing water. Leaves were green again after two weeks.', null],
            [$karim, $animal, 'Bull Raja (T-02)', 60, null, $completed, 'Small cut on the back leg from a fence wire.',
                'Cleaned the wound and kept flies away. Healed in about ten days.', null],
            [$salma, $animal, 'Layer hens (shed 2)', 1, 4, $ongoing, 'Some hens stopped laying and look weak.',
                'Separated the weak birds and cleaned the shed. Vet visit requested.', false],
            [$salma, $crop, 'Potato field, 4 bigha', 12, 2, $ongoing, 'Brown spots with rings on the lower leaves (possible early blight).',
                'Agriculture office advised removing badly affected leaves and avoiding watering the leaves. Check spread in two weeks.', false],
            [$salma, $animal, 'Cow Shada (B-01)', 20, -6, $ongoing, 'Mild swelling on the udder after calving.',
                'Vet checked her. Milking fully twice a day and keeping the shed clean.', true],
        ];

        foreach ($records as [$farmer, $category, $subject, $treatedDaysAgo, $followUpDays, $status, $problem, $notes, $reminderDone]) {
            $record = new TreatmentRecord([
                'category' => $category, 'subject_name' => $subject, 'problem' => $problem, 'treatment_notes' => $notes,
                'treated_on' => now()->subDays($treatedDaysAgo)->toDateString(),
                'follow_up_date' => $followUpDays === null ? null : now()->addDays($followUpDays)->toDateString(),
                'status' => $status,
            ]);
            $record->farmer_id = $farmer->id;
            $record->created_at = now()->subDays($treatedDaysAgo);
            $record->updated_at = $record->created_at;
            $record->save();

            if ($record->follow_up_date === null || $status === $completed) {
                continue;
            }

            // The same reminder TreatmentController::syncReminder creates for an ongoing record with a follow-up date.
            $reminder = new Reminder;
            $reminder->user_id = $farmer->id;
            $reminder->source_type = TreatmentRecord::class;
            $reminder->source_id = $record->id;
            $reminder->title = 'Follow-up: '.$subject;
            $reminder->notes = $problem;
            $reminder->remind_on = $record->follow_up_date;
            $reminder->completed_at = $reminderDone ? now()->subDay() : null;
            $reminder->save();
        }

        $this->command?->info('Added '.count($records).' demo treatment records with their reminders (Karim 01700000001, Salma 01700000002).');
    }
}
