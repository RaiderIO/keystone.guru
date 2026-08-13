<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
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
        $failures         = [];
        $unrelatedMdtData = [];

        // Cover the current season AND the newest one of the same expansion. getCurrentSeason() alone
        // stops gating a season the moment the next one is seeded but has not started yet - which is
        // exactly when its mapping is being imported and most needs checking. Scoping the second lookup
        // to the current expansion keeps the timewalking season (id 7, seeded with a sentinel start of
        // 2050) from dragging in a whole other expansion's dungeons.
        $currentRetailSeason = app(SeasonServiceInterface::class)->getCurrentSeason();
        $this->assertNotNull($currentRetailSeason, 'Expected a current retail season to exist');

        // "Newest of this expansion", which is the current season itself while nothing later is seeded.
        $newestRetailSeason = Season::where('expansion_id', $currentRetailSeason->expansion_id)
            ->orderByDesc('start')
            ->first();

        $seasons = collect([$currentRetailSeason, $newestRetailSeason])
            ->filter()
            ->unique('id');

        $dungeonIds = $seasons->flatMap(static fn(Season $season) => $season->dungeons->pluck('id'))->unique();

        $dungeons = Dungeon::with(['floors', 'npcs', 'mappingVersions.gameVersion'])
            ->whereIn('id', $dungeonIds)
            ->get()
            ->filter(static fn(Dungeon $dungeon) => Conversion::hasMDTDungeonName($dungeon->key));

        $this->assertNotEmpty($dungeons, 'Expected at least one MDT supported dungeon to check');

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

            // Without this the test passes for any dungeon that yields no clones at all - a missing lua
            // file, or a floor set that filtered down to nothing, both return an empty collection and
            // would otherwise contribute zero failures and look like full coverage.
            $this->assertNotEmpty($mdtClones, sprintf('%s produced no MDT clones at all', $dungeon->key));

            $mappingEnemies = $mappingVersion->enemies()->whereNotNull('mdt_id')->get();

            // MDT occasionally ships one dungeon's data under another dungeon's name - Midnight 6.2.1
            // has DenOfNalorakk.lua duplicated as TheBlindingVale.lua (see #3995). Our mapping is then
            // the correct one and MDT's is not, so measuring coverage against it reports every clone as
            // missing and drowns out the real gaps this test exists to find. Skip such a dungeon: a
            // non-empty mapping version sharing no NPC at all with MDT's clones is never a coverage
            // problem, and the skip lifts itself the moment MDT ships the right file.
            $sharesAnyNpc = $mappingEnemies
                ->pluck('npc_id')
                ->intersect($mdtClones->pluck('npc_id'))
                ->isNotEmpty();

            if ($mappingEnemies->isNotEmpty() && !$sharesAnyNpc) {
                $unrelatedMdtData[] = $dungeon->key;

                continue;
            }

            // Build a lookup of KG enemy (effectiveNpcId_mdtId) pairs for this mapping version
            $kgPairs = $mappingEnemies
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
        if ($unrelatedMdtData !== []) {
            fwrite(STDERR, sprintf(
                "\nMDTNpcMappingCoverage: skipped %s - MDT ships unrelated data for it, see #3995\n",
                implode(', ', $unrelatedMdtData),
            ));
        }

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
