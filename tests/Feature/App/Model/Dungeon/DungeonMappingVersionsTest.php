<?php

namespace Tests\Feature\App\Model\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
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
        $gameVersion   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $dungeon       = $this->findDungeonWithMultipleMappingVersionsFor($gameVersion);
        $expected      = $this->newestMappingVersion($dungeon, $gameVersion);
        $loadedDungeon = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id)->loadMappingVersions());

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        // Act
        $mappingVersion = $loadedDungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        // Assert
        $this->assertSame(0, $queries);
        $this->assertSame($expected->id, $mappingVersion?->id);
    }

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenUnloadedMappingVersions_loadsTheRelationAndReturnsTheNewest(): void
    {
        // Arrange
        $gameVersion  = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $dungeon      = $this->findDungeonWithMultipleMappingVersionsFor($gameVersion);
        $expected     = $this->newestMappingVersion($dungeon, $gameVersion);
        $freshDungeon = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id));
        $this->assertFalse($freshDungeon->relationLoaded('mappingVersions'));

        // Act
        $mappingVersion = app('model-cache')->runDisabled(static fn() => $freshDungeon->getCurrentMappingVersionForGameVersion($gameVersion));

        // Assert
        $this->assertTrue($freshDungeon->relationLoaded('mappingVersions'));
        $this->assertSame($expected->id, $mappingVersion?->id);
    }

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenMultipleMappingVersions_returnsAModelThatMayLazyLoad(): void
    {
        // Arrange
        $gameVersion  = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $dungeon      = $this->findDungeonWithMultipleMappingVersionsFor($gameVersion);
        $freshDungeon = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id));

        // Act
        $floorUnionCount = app('model-cache')->runDisabled(
            static fn() => $freshDungeon->getCurrentMappingVersionForGameVersion($gameVersion)?->floorUnions->count(),
        );

        // Assert
        $this->assertIsInt($floorUnionCount);
    }

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenRepeatedCalls_runsNoFurtherQueries(): void
    {
        // Arrange
        $retail       = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $classicEra   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        [$dungeon]    = $this->findDungeon(gameVersion: $retail);
        $freshDungeon = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id));
        $expected     = app('model-cache')->runDisabled(static fn() => $freshDungeon->getCurrentMappingVersionForGameVersion($retail));

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        // Act
        $mappingVersion = app('model-cache')->runDisabled(static function () use ($freshDungeon, $retail, $classicEra): ?MappingVersion {
            $freshDungeon->getCurrentMappingVersionForGameVersion($classicEra);

            return $freshDungeon->getCurrentMappingVersionForGameVersion($retail);
        });

        // Assert
        $this->assertSame(0, $queries);
        $this->assertSame($expected?->id, $mappingVersion?->id);
    }

    #[Test]
    public function getCurrentMappingVersionForGameVersion_givenNoMappingVersionForGameVersion_returnsNull(): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        [$dungeon]   = $this->findDungeon(
            constraint: static fn(Builder $query) => $query->whereDoesntHave(
                'mappingVersions',
                static fn(Builder $mappingVersions) => $mappingVersions->where('game_version_id', $gameVersion->id),
            ),
        );
        $freshDungeon = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id));

        // Act
        $mappingVersion = app('model-cache')->runDisabled(static fn() => $freshDungeon->getCurrentMappingVersionForGameVersion($gameVersion));

        // Assert
        $this->assertNull($mappingVersion);
        $this->assertNotEmpty($freshDungeon->mappingVersions);
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

    #[Test]
    public function reloadMappingVersions_givenMappingVersionWrittenAfterLoading_returnsIt(): void
    {
        // Arrange
        $gameVersion   = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        [$dungeon]     = $this->findDungeon(gameVersion: $gameVersion);
        $loadedDungeon = app('model-cache')->runDisabled(static fn() => Dungeon::findOrFail($dungeon->id)->loadMappingVersions());
        $current       = $loadedDungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $this->assertNotNull($current);

        $newMappingVersionId = null;

        try {
            $newMappingVersionId = MappingVersion::insertGetId([
                'game_version_id'                 => $gameVersion->id,
                'dungeon_id'                      => $dungeon->id,
                'version'                         => $current->version + 1,
                'enemy_forces_required'           => 0,
                'enemy_forces_required_teeming'   => 0,
                'enemy_forces_shrouded'           => 0,
                'enemy_forces_shrouded_zul_gamux' => 0,
                'timer_max_seconds'               => 0,
                'facade_enabled'                  => false,
                'created_at'                      => now(),
                'updated_at'                      => now(),
            ]);

            // Act
            $staleMappingVersion    = $loadedDungeon->getCurrentMappingVersionForGameVersion($gameVersion);
            $reloadedMappingVersion = app('model-cache')->runDisabled(
                static fn() => $loadedDungeon->reloadMappingVersions()->getCurrentMappingVersionForGameVersion($gameVersion),
            );

            // Assert
            $this->assertSame($current->id, $staleMappingVersion?->id);
            $this->assertSame($newMappingVersionId, $reloadedMappingVersion?->id);
        } finally {
            if ($newMappingVersionId !== null) {
                MappingVersion::query()->whereKey($newMappingVersionId)->delete();
            }
        }
    }

    private function findDungeonWithMultipleMappingVersionsFor(GameVersion $gameVersion): Dungeon
    {
        [$dungeon] = $this->findDungeon(
            gameVersion: $gameVersion,
            constraint: static fn(Builder $query) => $query->whereHas(
                'mappingVersions',
                static fn(Builder $mappingVersions) => $mappingVersions->where('game_version_id', $gameVersion->id),
                '>=',
                2,
            ),
        );

        return $dungeon;
    }

    private function newestMappingVersion(Dungeon $dungeon, GameVersion $gameVersion): MappingVersion
    {
        return MappingVersion::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('game_version_id', $gameVersion->id)
            ->orderByDesc('version')
            ->firstOrFail();
    }
}
