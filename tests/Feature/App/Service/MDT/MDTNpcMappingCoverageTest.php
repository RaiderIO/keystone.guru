<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('UsesLua')]
#[Group('MDTNpcMappingCoverage')]
final class MDTNpcMappingCoverageTest extends PublicTestCase
{
    #[Test]
    public function mdtNpcMapping_givenAllDungeons_hasNoUnmappedClones(): void
    {
        // Arrange
        $failures = [];

        $currentRetailSeason = app(SeasonServiceInterface::class)->getCurrentSeason();

        $dungeons = Dungeon::with(['floors', 'npcs', 'mappingVersions.gameVersion'])
            ->whereIn('id', $currentRetailSeason->dungeons->pluck('id'))
            ->get()
            ->filter(static fn(Dungeon $dungeon) => Conversion::hasMDTDungeonName($dungeon->key));

        foreach ($dungeons as $dungeon) {
            // Get the latest mapping version per game version
            $mappingVersion = $dungeon->mappingVersions
                ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL])
                ->sortByDesc('id')
                ->first();

            $mdtClones = app(MDTDungeon::class, [
                'cacheService'       => app(CacheServiceInterface::class),
                'coordinatesService' => app(CoordinatesServiceInterface::class),
                'dungeon'            => $dungeon,
            ])->getClonesAsEnemies($mappingVersion, $dungeon->floors);

            // Build a lookup of KG enemy (effectiveNpcId_mdtId) pairs for this mapping version
            $kgPairs = $mappingVersion->enemies()
                ->whereNotNull('mdt_id')
                ->get()
                ->map(static fn(Enemy $enemy) => sprintf('%d_%d', $enemy->mdt_npc_id ?? $enemy->npc_id, $enemy->mdt_id))
                ->flip();

            foreach ($mdtClones as $clone) {
                // Skip teeming clones — teeming is retired and gaps here are expected
                if ($clone->teeming === Enemy::TEEMING_VISIBLE) {
                    continue;
                }

                $key = sprintf('%d_%d', $clone->npc_id, $clone->mdt_id);
                if (!$kgPairs->has($key)) {
                    $failures[] = sprintf(
                        '%s | game_version=%s | mapping_version=%d | npc_id=%d | mdt_id=%d',
                        $dungeon->key,
                        $mappingVersion->gameVersion->key,
                        $mappingVersion->version,
                        $clone->npc_id,
                        $clone->mdt_id,
                    );
                }
            }
        }

        // Assert
        $this->assertEmpty(
            $failures,
            sprintf(
                "%d MDT clone(s) have no matching KG enemy:\n%s",
                count($failures),
                implode("\n", $failures),
            ),
        );
    }
}
