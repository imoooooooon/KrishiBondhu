<?php

namespace App\Console\Commands;

use App\Models\Cattle;
use App\Models\Role;
use App\Models\User;
use App\Services\CattleHealthHistoryService;
use App\Services\CattleService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * F6 demo data, for local development only (spec §19). DELETE BEFORE RELEASE.
 *
 *   php artisan kb:demo-cattle seed               add the demo cattle (safe to run again)
 *   php artisan kb:demo-cattle credentials        print the demo logins and links
 *   php artisan kb:demo-cattle cleanup            list what would be removed (dry run)
 *   php artisan kb:demo-cattle cleanup --apply    remove it
 *
 * Provenance: everything this command creates is recorded in a private manifest
 * (storage/app/private/demo/cattle-demo.json, ignored by Git). Cleanup removes ONLY records in that manifest,
 * and skips any animal changed during testing (its version or health entries differ). Accounts are removed only
 * if this command created them and nothing else (crops, jobs, other cattle) uses them.
 *
 * Photos: downloaded once from Unsplash's image CDN into storage/app/private/demo/cattle-sources/, then reused,
 * so later runs work offline. They go through the same CattleService (GD re-encoding) as a farmer's upload.
 */
class DemoCattleCommand extends Command
{
    protected $signature = 'kb:demo-cattle {action=seed : seed, credentials or cleanup} {--apply : With cleanup: really delete}';

    protected $description = 'Local demo data for F6 cattle profiles: seed, credentials, cleanup (dry run unless --apply)';

    private const MANIFEST = 'demo/cattle-demo.json';

    private const SOURCES = 'demo/cattle-sources';

    public function handle(CattleService $cattle, CattleHealthHistoryService $history): int
    {
        // Fail closed: only on a developer's computer (or in tests). There is no option to force it elsewhere.
        if (! app()->environment(['local', 'testing'])) {
            $this->error('kb:demo-cattle runs only when APP_ENV is local. Nothing was changed.');

            return self::FAILURE;
        }

        return match ($this->argument('action')) {
            'seed' => $this->seed($cattle, $history),
            'credentials' => $this->credentials(),
            'cleanup' => $this->cleanup(),
            default => $this->invalidAction(),
        };
    }

    private function seed(CattleService $service, CattleHealthHistoryService $history): int
    {
        $fixtures = require database_path('seeders/data/cattle-demo-fixtures.php');
        $manifest = $this->readManifest() ?? ['reference_date' => now()->toDateString(), 'users' => [], 'cattle' => []];
        $reference = Carbon::parse($manifest['reference_date']);

        foreach ($fixtures['accounts'] as $phone => $account) {
            $user = $this->demoAccount($phone, $account['name']);
            if ($user === null) {
                $this->warn("{$phone} belongs to a non-demo account, so it was not used.");

                continue;
            }
            $manifest['users'][$phone] ??= ['id' => $user->id, 'created' => $user->wasRecentlyCreated];
        }

        $added = 0;
        foreach ($fixtures['cattle'] as $key => [$phone, $photoId, $profile, $age, $entries, $archived]) {
            if (isset($manifest['cattle'][$key])) {
                $this->line("  {$key}: already seeded (not recreated, even if you changed or removed it).");

                continue;
            }
            $owner = isset($manifest['users'][$phone]) ? User::find($manifest['users'][$phone]['id']) : null;
            $photo = $owner ? $this->sourcePhoto($photoId, $fixtures['photos'][$photoId][0]) : null;
            if (! $owner || ! $photo) {
                $this->warn("  {$key}: skipped (".(! $owner ? 'owner account not available' : 'photo could not be downloaded').').');

                continue;
            }

            $ageData = match ($age[0]) {
                'exact' => ['basis' => 'exact', 'birth_date' => $reference->copy()->subDays($age[1])->toDateString(), 'months' => null],
                'estimated' => ['basis' => 'estimated', 'birth_date' => null, 'months' => $age[1]],
                default => ['basis' => 'unknown', 'birth_date' => null, 'months' => null],
            };

            [$animal] = $service->register($owner, $profile, $ageData, (string) Str::uuid(), $photo, null);
            @unlink($photo->getRealPath()); // the temporary copy; the cached source stays
            $entryIds = [];
            foreach ($entries as [$type, $title, $daysAgo, $details]) {
                [$entry] = $history->create($animal, $owner, [
                    'entry_type' => $type, 'title' => $title, 'details' => $details, 'source_label' => 'Demo data',
                    'occurred_on' => $daysAgo === null ? null : $reference->copy()->subDays($daysAgo)->toDateString(),
                ], (string) Str::uuid());
                $entryIds[] = $entry->id;
            }
            if ($archived) {
                $animal = $service->archive($animal, $animal->fresh()->version);
            }

            $fresh = $animal->fresh();
            $manifest['cattle'][$key] = ['id' => $fresh->id, 'version' => $fresh->version, 'entries' => $entryIds,
                'files' => [$fresh->photo_path, $fresh->photo_thumbnail_path]];
            $this->writeManifest($manifest); // after every animal, so an interrupted run knows what it made
            $this->line("  {$key}: added {$fresh->displayId()} for {$phone}".($archived ? ' (archived)' : ''));
            $added++;
        }

        $this->writeManifest($manifest);
        $this->info("Added {$added} demo cattle.");

        return $this->credentials();
    }

    private function credentials(): int
    {
        $fixtures = require database_path('seeders/data/cattle-demo-fixtures.php');
        $manifest = $this->readManifest();

        $rows = [];
        foreach ($fixtures['accounts'] as $phone => $account) {
            $user = User::where('phone', $phone)->first();
            $count = $user ? $user->cattle()->count() : 0;
            $rows[] = [$account['label'], $phone, $user ? 'password' : '(not created)', $count];
        }
        $this->table(['Account', 'Mobile (login)', 'Password', 'Cattle'], $rows);

        $this->line('Log in:        '.route('login'));
        $this->line('My cattle:     '.route('cattle.index'));
        $this->line('Register:      '.route('cattle.create'));
        if ($manifest && isset($manifest['cattle']['A-01']) && ($example = Cattle::find($manifest['cattle']['A-01']['id']))) {
            $this->line('Example cow:   '.route('cattle.show', $example).'  ('.$example->displayId().', log in as 01700000001)');
        }
        $this->line('Marketplace:   '.route('marketplace.index'));
        $this->line('These are local demo logins only. Remove with: php artisan kb:demo-cattle cleanup --apply');

        return self::SUCCESS;
    }

    private function cleanup(): int
    {
        $manifest = $this->readManifest();
        if (! $manifest) {
            $this->info('No demo manifest found: nothing to clean up.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');
        $this->line($apply ? 'Removing demo cattle:' : 'Dry run (add --apply to delete):');

        foreach ($manifest['cattle'] as $key => $record) {
            $animal = Cattle::find($record['id']);
            if (! $animal) {
                $this->line("  {$key}: already gone.");
                unset($manifest['cattle'][$key]);

                continue;
            }
            $entryIds = $animal->healthEntries()->pluck('id')->sort()->values()->all();
            $expected = collect($record['entries'])->sort()->values()->all();
            if ($animal->version !== $record['version'] || $entryIds !== $expected) {
                $this->warn("  {$key} ({$animal->displayId()}): changed during testing, kept for you to review.");

                continue;
            }

            $this->line("  {$key} ({$animal->displayId()}): ".($apply ? 'removed' : 'would be removed').', with '.count($entryIds).' health entries and 2 photo files.');
            if ($apply) {
                DB::transaction(function () use ($animal) {
                    $animal->healthEntries()->delete();
                    $animal->delete();
                });
                Storage::disk('local')->delete($record['files']);
                unset($manifest['cattle'][$key]);
            }
        }

        foreach ($manifest['users'] as $phone => $user) {
            $account = User::find($user['id']);
            $inUse = $account && ($account->cattle()->exists() || $account->cropListings()->withTrashed()->exists() || $account->farmJobs()->exists());
            if (! $user['created'] || ! $account || $inUse) {
                $this->line("  account {$phone}: kept (".(! $user['created'] ? 'it existed before' : ($account ? 'still in use' : 'already gone')).').');

                continue;
            }
            $this->line("  account {$phone}: ".($apply ? 'removed' : 'would be removed').'.');
            if ($apply) {
                $account->roles()->detach();
                $account->delete();
                unset($manifest['users'][$phone]);
            }
        }

        if ($apply) {
            $manifest['cattle'] === [] ? Storage::disk('local')->delete(self::MANIFEST) : $this->writeManifest($manifest);
        }

        return self::SUCCESS;
    }

    /** The demo account for this phone: created if missing; reused only if it is clearly a demo account. */
    private function demoAccount(string $phone, string $name): ?User
    {
        $user = User::firstOrCreate(['phone' => $phone], ['name' => $name, 'password' => 'password', 'locale' => 'en']);
        if ($user->wasRecentlyCreated) {
            $user->roles()->attach(Role::where('slug', Role::FARMER)->pluck('id'));

            return $user;
        }

        if (! str_contains($user->name, '(demo')) {
            return null; // a real person's account: never touch it
        }
        if (! $user->hasRole(Role::FARMER)) {
            $user->roles()->attach(Role::where('slug', Role::FARMER)->pluck('id'));
        }

        return $user;
    }

    /** The cached source photo as an upload, downloading it from Unsplash's CDN the first time. */
    private function sourcePhoto(string $photoId, string $cdnId): ?UploadedFile
    {
        $disk = Storage::disk('local');
        $path = self::SOURCES.'/'.$photoId.'.jpg';

        if (! $disk->exists($path)) {
            try {
                $response = Http::timeout(60)
                    ->withUserAgent('KrishiBondhu-demo (CSE470 student project, development only)')
                    ->withOptions(['verify' => $this->certificateBundle()])
                    ->get("https://images.unsplash.com/{$cdnId}", ['w' => 1600, 'q' => 85, 'fm' => 'jpg']);
                if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/jpeg')) {
                    return null;
                }
                $disk->put($path, $response->body());
            } catch (Throwable) {
                return null;
            }
        }

        // A copy, because the upload pipeline may treat the file as a temporary upload.
        $copy = tempnam(sys_get_temp_dir(), 'kb-cattle-');
        file_put_contents($copy, $disk->get($path));

        return new UploadedFile($copy, $photoId.'.jpg', 'image/jpeg', null, true);
    }

    /** HTTPS is always verified (see CropListingDemoSeeder for why the Git for Windows list is a fallback). */
    private function certificateBundle(): string|bool
    {
        foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile'), 'C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt'] as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return true;
    }

    private function readManifest(): ?array
    {
        $disk = Storage::disk('local');

        return $disk->exists(self::MANIFEST) ? json_decode($disk->get(self::MANIFEST), true) : null;
    }

    private function writeManifest(array $manifest): void
    {
        Storage::disk('local')->put(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function invalidAction(): int
    {
        $this->error('Unknown action. Use: seed, credentials or cleanup [--apply].');

        return self::FAILURE;
    }
}
