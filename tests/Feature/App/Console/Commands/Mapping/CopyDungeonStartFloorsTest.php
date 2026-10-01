<?php

namespace Tests\Feature\App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('DungeonStart')]
final class CopyDungeonStartFloorsTest extends PublicTestCase
{
    #[Test]
    public function handle_givenCopyToAnotherDungeon_movesDungeonStartsOntoTheTargetDungeonsFloors(): void
    {
        // Arrange
        /** @var GameVersion $gameVersion */
        $gameVersion                     = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        [$sourceDungeon, $targetDungeon] = $this->getDungeonsWithMatchingFloorCounts($gameVersion);
        $maxMappingVersionIdBefore       = (int)MappingVersion::query()->max('id');

        $createdMappingVersion = null;

        try {
            // Act
            Artisan::call('mapping:copy', [
                'gameVersion'   => $gameVersion->key,
                'sourceDungeon' => $sourceDungeon->key,
                'targetDungeon' => $targetDungeon->key,
            ]);

            // Assert
            /** @var MappingVersion|null $createdMappingVersion */
            $createdMappingVersion = MappingVersion::query()
                ->where('id', '>', $maxMappingVersionIdBefore)
                ->where('dungeon_id', $targetDungeon->id)
                ->first();
            $this->assertNotNull($createdMappingVersion, 'mapping:copy must create a mapping version on the target dungeon.');

            $dungeonStartFloorIds = DungeonStart::query()
                ->where('mapping_version_id', $createdMappingVersion->id)
                ->pluck('floor_id');
            $this->assertNotEmpty($dungeonStartFloorIds, 'Precondition: the copied mapping version has dungeon starts.');
            $this->assertEmpty(
                $dungeonStartFloorIds->diff($targetDungeon->floors()->pluck('id')),
                'Every copied dungeon start must sit on one of the target dungeon\'s floors.',
            );
        } finally {
            // An Eloquent delete: MappingVersion::boot()'s `deleting` cascade removes what was cloned.
            $createdMappingVersion?->delete();
        }
    }

    /**
     * @return array{0: Dungeon, 1: Dungeon}
     */
    private function getDungeonsWithMatchingFloorCounts(GameVersion $gameVersion): array
    {
        $dungeons = Dungeon::query()
            ->whereNotNull('challenge_mode_id')
            ->withCount('floors')
            ->get();

        foreach ($dungeons as $sourceDungeon) {
            $sourceMappingVersion = $sourceDungeon->getCurrentMappingVersionForGameVersion($gameVersion);
            if ($sourceMappingVersion === null || !$sourceMappingVersion->dungeonStarts()->exists()) {
                continue;
            }

            $targetDungeon = $dungeons->first(static fn(Dungeon $dungeon) => $dungeon->id !== $sourceDungeon->id
                && $dungeon->floors_count === $sourceDungeon->floors_count);
            if ($targetDungeon !== null) {
                return [$sourceDungeon, $targetDungeon];
            }
        }

        $this->fail('No two dungeons with the same floor count, one with dungeon starts, found for testing.');
    }
}
