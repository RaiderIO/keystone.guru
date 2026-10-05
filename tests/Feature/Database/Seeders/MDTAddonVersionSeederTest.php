<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\MDTAddonVersion;
use Database\Seeders\MDTAddonVersionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards that MDTAddonVersionSeeder imports the committed database/data/mdt/addon_versions.json into the
 * mdt_addon_versions table verbatim. The table is already seeded, so the test first removes and corrupts rows
 * inside a transaction that is always rolled back - otherwise a seeder that writes nothing would pass.
 */
#[Group('MDT')]
#[Group('MDTAddonVersion')]
final class MDTAddonVersionSeederTest extends PublicTestCase
{
    private const DATA_PATH = 'data/mdt/addon_versions.json';

    #[Test]
    public function run_givenCommittedJson_populatesTableForEveryEntry(): void
    {
        // Arrange
        /** @var array<string, string> $expected */
        $expected = json_decode(file_get_contents(database_path(self::DATA_PATH)), true);

        /** @var array<int, string> $releaseDatesByAddonVersion */
        $releaseDatesByAddonVersion = collect($expected)->mapWithKeys(static fn(string $releasedAt, int|string $addonVersion): array => [(int)$addonVersion => $releasedAt])->all();
        $removedAddonVersion        = (int)array_key_last($releaseDatesByAddonVersion);
        $corruptedAddonVersion      = (int)array_key_first($releaseDatesByAddonVersion);

        DB::beginTransaction();

        try {
            MDTAddonVersion::query()->whereKey($removedAddonVersion)->delete();
            MDTAddonVersion::query()->whereKey($corruptedAddonVersion)->update(['released_at' => '2000-01-01 00:00:00']);

            // Act
            $this->seed(MDTAddonVersionSeeder::class);

            // Assert - every committed entry is present with its release date.
            $this->assertSame(count($expected), MDTAddonVersion::query()->count());

            /** @var MDTAddonVersion $newest */
            $newest = MDTAddonVersion::query()->findOrFail(6120);
            $this->assertSame('2026-07-03', $newest->released_at->toDateString());

            $releasedAtByAddonVersion = MDTAddonVersion::query()
                ->get()
                ->mapWithKeys(static fn(MDTAddonVersion $addonVersion): array => [$addonVersion->addon_version => $addonVersion->released_at->toDateTimeString()]);
            foreach ([$removedAddonVersion, $corruptedAddonVersion] as $addonVersion) {
                $this->assertSame(
                    Carbon::parse($releaseDatesByAddonVersion[$addonVersion])->toDateTimeString(),
                    $releasedAtByAddonVersion->get($addonVersion),
                    sprintf('Addon version %d was not restored from the JSON', $addonVersion),
                );
            }
        } finally {
            DB::rollBack();
        }
    }
}
