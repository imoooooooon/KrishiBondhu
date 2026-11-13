<?php

namespace Tests\Feature\Cattle;

use App\Models\Cattle;
use App\Models\Role;
use App\Models\User;
use App\Services\CattleAge;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Shared test data for the F6 tests. Photos are real images made by GD. */
trait CattleHelpers
{
    protected function farmer(): User
    {
        return User::factory()->withRoles(Role::FARMER)->create();
    }

    protected function day(int $offset): string
    {
        return Carbon::parse(CattleAge::today())->addDays($offset)->toDateString();
    }

    /** @return array<string, mixed> a complete, valid "Register cattle" form */
    protected function registerForm(array $overrides = []): array
    {
        return array_merge([
            'submission_token' => (string) Str::uuid(),
            'nickname' => 'Lali',
            'farm_tag' => 'T-07',
            'sex' => 'female',
            'breed_known' => 'yes',
            'breed_description' => 'Red Chittagong',
            'age_basis' => 'exact',
            'birth_date' => $this->day(-800),
            'mother_details' => 'Tag T-02, my farm',
            'father_details' => '',
            'photo' => UploadedFile::fake()->image('cow.jpg', 640, 400),
        ], $overrides);
    }

    /** @return array<string, mixed> a valid edit form for $cattle that keeps the age */
    protected function editForm(Cattle $cattle, array $overrides = []): array
    {
        return array_merge([
            'operation' => 'save',
            'version' => $cattle->version,
            'nickname' => $cattle->nickname,
            'farm_tag' => $cattle->farm_tag,
            'sex' => $cattle->sex,
            'breed_known' => $cattle->breed_description ? 'yes' : 'no',
            'breed_description' => $cattle->breed_description,
            'mother_details' => $cattle->mother_details,
            'father_details' => $cattle->father_details,
            'age_update' => 'keep',
        ], $overrides);
    }

    /** @return array<string, mixed> a valid health-entry form */
    protected function entryForm(array $overrides = []): array
    {
        return array_merge([
            'submission_token' => (string) Str::uuid(),
            'entry_type' => 'vaccination',
            'title' => 'FMD vaccine',
            'date_known' => 'yes',
            'occurred_on' => $this->day(-30),
            'details' => 'Given at the upazila vaccination camp.',
            'source_label' => 'My notebook',
        ], $overrides);
    }
}
