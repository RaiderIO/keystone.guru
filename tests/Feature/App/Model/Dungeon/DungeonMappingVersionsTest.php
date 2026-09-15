<?php

namespace Tests\Feature\App\Model\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Dungeon')]
#[Group('DungeonMappingVersions')]
final class DungeonMappingVersionsTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenLoadedMappingVersions_returnsTheNewestWithoutAQuery(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        [$dungeon]   = $this->findDungeon(
            gameVersion: $gameVersion,
            constraint: static fn(Builder $query) => $query->whereHas(
                'mappingVersions',
                static fn(Builder $mappingVersions) => $mappingVersions->where('game_version_id', $gameVersion->id),
                '>=',
                2,
            ),
        );

        $expected = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id)
            ->getCurrentMappingVersionForGameVersion($gameVersion));

        $loadedDungeon = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id)->loadMappingVersions());

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        // Act
        $mappingVersion = $loadedDungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        // Assert
        $this->assertSame(0, $queries);
        $this->assertSame($expected->id, $mappingVersion->id);
    }

    #[Test]
    public function loadMappingVersions_givenLoadedMappingVersions_runsNoQuery(): void
    {
        // Arrange
        [$dungeon] = $this->findDungeon();
        $dungeon   = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id)->loadMappingVersions());
        $expected  = $dungeon->mappingVersions->pluck('id')->all();

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        // Act
        $mappingVersionIds = app('model-cache')->runDisabled(static fn() => $dungeon->loadMappingVersions()->mappingVersions->pluck('id')->all());

        // Assert
        $this->assertSame(0, $queries);
        $this->assertSame($expected, $mappingVersionIds);
    }
}
