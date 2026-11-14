<?php

namespace Database\Seeders;

use App\Exceptions\AiGuidanceException;
use App\Models\Role;
use App\Models\SoilGuidanceRecord;
use App\Services\SoilGuidanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo data for F15 (soil-based fertilizer guidance), for development and the viva only. DELETE BEFORE RELEASE.
 *     php artisan db:seed --class=SoilGuidanceDemoSeeder
 *
 * Gives the demo farmers a short guidance history: completed answers in English and Bangla, and one failed request
 * that can be retried. It does NOT call Gemini: the answers below were written by hand as examples and are saved with
 * ai_model "demo-seeder", so they can never be mistaken for real AI output. Each answer still passes
 * SoilGuidanceService::validateGuidance, the same check a real AI answer must pass.
 * To show a live AI answer in the viva, make a new request in the app (needs GEMINI_API_KEY in .env).
 * Password for every account: "password". Safe to run twice: nothing is added if a demo farmer has a record.
 */
class SoilGuidanceDemoSeeder extends Seeder
{
    use DemoAccounts;

    public function run(SoilGuidanceService $service): void
    {
        if ($this->refuseInProduction()) {
            return;
        }

        $karim = $this->demoUser('01700000001', 'Karim Mia (demo farmer)', [Role::FARMER]);
        $salma = $this->demoUser('01700000002', 'Salma Khatun (demo farmer and worker)', [Role::FARMER, Role::WORKER]);
        $hamid = $this->demoUser('01700000004', 'Abdul Hamid (demo farmer)', [Role::FARMER]);

        if (SoilGuidanceRecord::whereIn('farmer_id', [$karim->id, $salma->id, $hamid->id])->exists()) {
            $this->command?->info('Demo soil guidance records already exist. Nothing was added.');

            return;
        }

        $limits = [
            'Only a laboratory soil test can show the real nutrient levels and pH of your soil.',
            'Soil colour and feel alone cannot prove a nutrient shortage or excess.',
            'Exact fertilizer amounts cannot be known from what you can see and feel.',
        ];

        // [farmer, requested (days ago), locale, observations, guidance or null for a failed request]
        $records = [
            [$karim, 12, 'en', ['crop_name' => 'Aman paddy', 'soil_color' => 'dark', 'moisture' => 'waterlogged', 'texture' => 'sticky',
                'observations' => 'Water stands in the low part of the field for several days after rain.'], [
                    'observations_summary' => 'You are growing Aman paddy in dark, sticky soil that stays very wet, with standing water in the low part of the field.',
                    'general_soil_care' => [
                        'Make small drains at the low end of the field so extra water can leave after heavy rain.',
                        'Avoid walking or using machines on the field while it is very wet, so the soil does not become harder.',
                        'Leave the rice straw in the field after harvest to add organic matter.',
                    ],
                    'fertilizer_guidance' => [
                        'Apply urea in two or three smaller split doses instead of all at once, so less is washed away.',
                        'Ask your Upazila Agriculture Office for the recommended doses for Aman paddy in your area.',
                    ],
                    'limitations' => $limits,
                    'follow_up_questions' => ['Does the water drain away within one or two days, or stay longer?'],
                ]],
            [$karim, 3, 'en', ['crop_name' => 'Brinjal', 'soil_color' => 'brown', 'moisture' => 'dry', 'texture' => 'crumbly',
                'observations' => null], [
                    'observations_summary' => 'You are growing brinjal in brown soil that feels dry and crumbly.',
                    'general_soil_care' => [
                        'Cover the soil around the plants with straw or dry leaves to keep the moisture in.',
                        'Water in the early morning or evening so less water is lost in the heat.',
                        'Mix well-rotted cow dung or compost into the soil between the rows.',
                    ],
                    'fertilizer_guidance' => [
                        'Add organic manure before the next planting to help the soil hold water and nutrients.',
                        'Give fertilizer only when the soil is moist, and keep it away from the stems.',
                    ],
                    'limitations' => $limits,
                    'follow_up_questions' => ['How often can you water the field?', 'Have you used compost or manure in this field before?'],
                ]],
            [$salma, 7, 'bn', ['crop_name' => 'আলু', 'soil_color' => 'brown', 'moisture' => 'moist', 'texture' => 'sandy',
                'observations' => 'মাটি হাতে নিলে ঝুরঝুরে, পানি তাড়াতাড়ি নেমে যায়।'], [
                    'observations_summary' => 'আপনি বাদামি, বেলে ও আর্দ্র মাটিতে আলু চাষ করছেন, যেখানে পানি তাড়াতাড়ি নেমে যায়।',
                    'general_soil_care' => [
                        'জমিতে পচা গোবর বা কম্পোস্ট মেশান, যাতে বেলে মাটি বেশি পানি ধরে রাখতে পারে।',
                        'অল্প অল্প করে বারবার সেচ দিন, একবারে বেশি পানি দেবেন না।',
                        'আলুর সারিতে মাটি তুলে দিন, যাতে কন্দ ঢাকা থাকে।',
                    ],
                    'fertilizer_guidance' => [
                        'বেলে মাটি থেকে সার ধুয়ে যেতে পারে, তাই সার কয়েক ভাগে ভাগ করে দিন।',
                        'আলুর জন্য সঠিক সারের পরিমাণ জানতে উপজেলা কৃষি অফিসে যোগাযোগ করুন।',
                    ],
                    'limitations' => [
                        'শুধু ল্যাবরেটরিতে মাটি পরীক্ষা করেই মাটির আসল পুষ্টি ও পিএইচ জানা যায়।',
                        'মাটির রং ও স্পর্শ দেখে সারের সঠিক পরিমাণ বলা যায় না।',
                    ],
                    'follow_up_questions' => ['আপনি সপ্তাহে কতবার সেচ দিতে পারেন?'],
                ]],
            [$salma, 1, 'en', ['crop_name' => 'Mustard', 'soil_color' => 'yellowish', 'moisture' => 'not_sure', 'texture' => 'not_sure',
                'observations' => null], null], // failed: shows the retry button and message
            [$hamid, 5, 'en', ['crop_name' => 'Onion', 'soil_color' => 'reddish', 'moisture' => 'dry', 'texture' => 'sticky',
                'observations' => 'The soil cracks when it dries.'], [
                    'observations_summary' => 'You are growing onion in reddish, sticky soil that is dry and cracks as it dries.',
                    'general_soil_care' => [
                        'Add compost or well-rotted manure to make the sticky soil looser and easier for roots.',
                        'Water lightly and often so the soil does not dry out and crack.',
                        'Cover the soil with straw to keep it from drying too fast.',
                    ],
                    'fertilizer_guidance' => [
                        'Use organic manure before planting and ask the Upazila Agriculture Office for the right doses for onion.',
                    ],
                    'limitations' => $limits,
                    'follow_up_questions' => [],
                ]],
        ];

        foreach ($records as [$farmer, $daysAgo, $locale, $observations, $guidance]) {
            $record = new SoilGuidanceRecord($observations);
            $record->farmer_id = $farmer->id;
            $record->submission_token = (string) Str::uuid();
            $record->locale = $locale;
            $record->attempt_count = 1;
            $record->last_attempt_at = now()->subDays($daysAgo);
            $record->created_at = $record->last_attempt_at;
            $record->updated_at = $record->last_attempt_at;

            if ($guidance === null) {
                $record->status = SoilGuidanceRecord::STATUS_FAILED;
                $record->error_code = AiGuidanceException::RATE_LIMITED;
            } else {
                // The same check a real AI answer must pass before it is saved.
                $record->guidance = $service->validateGuidance(json_encode($guidance, JSON_UNESCAPED_UNICODE));
                $record->status = SoilGuidanceRecord::STATUS_COMPLETED;
                $record->ai_model = 'demo-seeder';
                $record->completed_at = $record->last_attempt_at->copy()->addSeconds(6);
            }

            $record->save();
        }

        $this->command?->info('Added '.count($records).' demo soil guidance records (Karim 01700000001, Salma 01700000002, Hamid 01700000004).');
    }
}
