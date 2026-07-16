<?php

namespace Tests\Feature\Database\Seeders;

use App\Models\MDTAddonVersion;
use Database\Seeders\MDTAddonVersionSeeder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Guards that MDTAddonVersionSeeder imports the committed database/data/mdt/addon_versions.json into the
 * mdt_addon_versions table verbatim. The seeder is idempotent (upsert), so re-running it leaves the shared
 * seeded database untouched.
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

        // Act - idempotent upsert, safe to run against the shared seeded database.
        $this->seed(MDTAddonVersionSeeder::class);

        // Assert - every committed entry is present with its release date.
        $this->assertSame(count($expected), MDTAddonVersion::query()->count());

        /** @var MDTAddonVersion $newest */
        $newest = MDTAddonVersion::query()->findOrFail(6120);
        $this->assertSame('2026-07-03', $newest->released_at->toDateString());
    }
}
