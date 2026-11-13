<?php

namespace Tests\Feature\Cattle;

use App\Models\Cattle;
use App\Models\CattleHealthEntry;
use App\Models\CropListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** F6 spec §19: the demo command is local-only, repeatable, and its cleanup removes only what it made. */
class DemoCattleCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $image = imagecreatetruecolor(320, 200);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();
        Http::fake(['images.unsplash.com/*' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg'])]);
    }

    public function test_seed_creates_the_fixture_set_once(): void
    {
        $this->artisan('kb:demo-cattle', ['action' => 'seed'])->assertSuccessful();

        $this->assertSame(12, Cattle::count());
        $this->assertSame(1, Cattle::archived()->count());
        $this->assertSame(12, CattleHealthEntry::count()); // as listed in database/seeders/data/cattle-demo-fixtures.php
        $this->assertSame([6, 4, 2, 0], array_map(fn ($phone) => User::where('phone', $phone)->first()->cattle()->count(), ['01700000001', '01700000002', '01700000004', '01700000005']));
        Http::assertSentCount(12);

        $this->artisan('kb:demo-cattle', ['action' => 'seed'])->assertSuccessful();
        $this->assertSame(12, Cattle::count(), 'a second run adds nothing');
        Http::assertSentCount(12); // photos are cached: no new downloads
    }

    public function test_cleanup_is_a_dry_run_by_default_and_keeps_edited_records_and_other_data(): void
    {
        $this->artisan('kb:demo-cattle', ['action' => 'seed']);
        $karim = User::where('phone', '01700000001')->first();
        CropListing::factory()->for($karim, 'farmer')->create(); // F1 data on the same demo account
        $edited = Cattle::where('nickname', 'Moni')->first();
        $edited->forceFill(['nickname' => 'Moni (edited)', 'version' => $edited->version + 1])->save(); // a manual test edit
        $mine = Cattle::factory()->for($karim, 'farmer')->create(); // a record the developer added by hand

        $this->artisan('kb:demo-cattle', ['action' => 'cleanup'])->expectsOutputToContain('would be removed')->assertSuccessful();
        $this->assertSame(13, Cattle::count(), 'dry run changes nothing');

        $this->artisan('kb:demo-cattle', ['action' => 'cleanup', '--apply' => true])->expectsOutputToContain('changed during testing')->assertSuccessful();

        $this->assertEqualsCanonicalizing([$edited->id, $mine->id], Cattle::pluck('id')->all());
        $this->assertSame(1, CropListing::count(), 'F1 data is untouched');
        $this->assertNotNull(User::where('phone', '01700000001')->first(), 'accounts still in use are kept');
    }

    public function test_refuses_to_run_outside_local_development(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('kb:demo-cattle', ['action' => 'seed'])->assertFailed();
        $this->assertSame(0, Cattle::count());
        $this->assertSame(0, User::count());
    }
}
