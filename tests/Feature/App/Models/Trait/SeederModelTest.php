<?php

namespace Tests\Feature\App\Models\Trait;

use App\Models\Dungeon;
use App\Models\Season;
use App\Models\SeasonDungeon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * SeederModel used to register a `deleting` listener returning false for everyone but an admin. It guarded
 * nothing - every route deleting one of these models sits behind `role:admin` - while silently turning
 * `$model->delete()` into a no-op wherever nobody is authenticated (Artisan commands, queued jobs, tests),
 * and swallowing any `deleting` listener a model registered in `booted()`, because Eloquent fires `deleting`
 * through the dispatcher's `until()`, which halts on the first non-null result.
 */
#[Group('SeederModel')]
final class SeederModelTest extends PublicTestCase
{
    #[Test]
    public function delete_givenNoAuthenticatedUser_removesTheRow(): void
    {
        // Arrange
        $this->actingAsGuest();

        $seasonDungeon = null;

        try {
            $seasonDungeon = SeasonDungeon::create([
                'season_id'  => Season::SEASON_MIDNIGHT_S1,
                'dungeon_id' => Dungeon::query()->firstOrFail()->id,
            ]);

            // Act
            $deleted = $seasonDungeon->delete();

            // Assert
            $this->assertTrue((bool)$deleted);
            $this->assertNull(SeasonDungeon::query()->find($seasonDungeon->id));
        } finally {
            SeasonDungeon::query()->whereKey($seasonDungeon?->id)->delete();

            new SeasonDungeon()->flushCache();
        }
    }
}
