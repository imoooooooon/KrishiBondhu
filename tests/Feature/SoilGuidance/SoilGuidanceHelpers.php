<?php

namespace Tests\Feature\SoilGuidance;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared test data for the F15 tests. The AI service is ALWAYS faked: no test sends a real request,
 * needs a real key or uses API quota. Http::preventStrayRequests() makes a forgotten fake fail loudly.
 */
trait SoilGuidanceHelpers
{
    protected const AI_URL = 'generativelanguage.googleapis.com/*';

    protected const TEST_KEY = 'test-key-never-real';

    protected function setUpSoilGuidance(): void
    {
        Storage::fake('local');
        Http::preventStrayRequests();
        config(['services.gemini.api_key' => self::TEST_KEY, 'services.gemini.model' => 'gemini-test-model']);
    }

    protected function farmer(): User
    {
        return User::factory()->withRoles(Role::FARMER)->create();
    }

    /** @return array<string, mixed> a complete, valid "Get soil guidance" form without a photo */
    protected function form(array $overrides = []): array
    {
        return array_merge([
            'submission_token' => (string) Str::uuid(),
            'crop_name' => 'Boro rice',
            'soil_color' => 'brown',
            'moisture' => 'moist',
            'texture' => 'sticky',
            'observations' => 'Water stands for two days after rain.',
        ], $overrides);
    }

    /** @return array<string, mixed> guidance in the structure the application expects */
    protected function guidance(array $overrides = []): array
    {
        return array_merge([
            'observations_summary' => 'You grow Boro rice in brown, moist, sticky soil where water stands after rain.',
            'general_soil_care' => ['Make small drains so extra water can leave the field.', 'Mix in compost before planting.'],
            'fertilizer_guidance' => ['Apply urea in two or three smaller splits instead of one large dose.'],
            'limitations' => ['Only a soil test can show the real nutrient levels and pH.'],
            'follow_up_questions' => ['Which crop did you grow here last season?'],
        ], $overrides);
    }

    /**
     * A successful answer in the shape of Gemini's Interactions API, with $text as the model's output.
     *
     * @return array<string, mixed>
     */
    protected function interaction(string $text, string $status = 'completed'): array
    {
        return [
            'id' => 'interaction-test',
            'object' => 'interaction',
            'status' => $status,
            'model' => 'gemini-test-model',
            'steps' => [
                ['type' => 'thought', 'summary' => [['type' => 'text', 'text' => 'Thinking about the soil.']]],
                ['type' => 'model_output', 'content' => [['type' => 'text', 'text' => $text]]],
            ],
        ];
    }

    /** Make the fake AI service answer with valid guidance (or the given guidance). */
    protected function fakeAiAnswer(?array $guidance = null): void
    {
        Http::fake([self::AI_URL => Http::response($this->interaction(json_encode($guidance ?? $this->guidance())))]);
    }
}
