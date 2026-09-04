<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\DungeonKey;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('API')]
#[Group('APICombatLog')]
#[Group('CombatLogRoute')]
#[Group('CombatLogRouteEnemyFailures')]
final class APICombatLogControllerCombatLogRouteEnemyFailuresTest extends APICombatLogControllerCombatLogRouteTestBase
{
    protected const string FIXTURES_ROOT_DIR = '../';

    protected function getDungeonKey(): string
    {
        return DungeonKey::MAGISTERS_TERRACE_MIDNIGHT->value;
    }

    #[Test]
    public function store_givenRouteWithUnresolvableNpcs_persistsEnemyFailures(): void
    {
        // Arrange
        $postBody          = $this->getJsonData('Midnight/midnight_s1_magisters_terrace_preseason', self::FIXTURES_ROOT_DIR);
        $countBefore       = CombatLogRouteEnemyFailure::where('dungeon_id', $this->dungeon->id)->count();
        $insertedIds       = [];
        $npcEnemyForcesIds = $this->giveFixtureNpcsEnemyForces($postBody);

        try {
            // Act
            $response = $this->post(route('api.v1.combatlog.route.store'), $postBody);
            $response->assertCreated();

            $insertedIds = CombatLogRouteEnemyFailure::where('dungeon_id', $this->dungeon->id)
                ->orderBy('id', 'desc')
                ->limit(500)
                ->pluck('id')
                ->toArray();

            // Assert
            $countAfter = CombatLogRouteEnemyFailure::where('dungeon_id', $this->dungeon->id)->count();
            $this->assertGreaterThan($countBefore, $countAfter, 'Expected at least one CombatLogRouteEnemyFailure to be persisted.');

            $failure = CombatLogRouteEnemyFailure::find($insertedIds[0]);
            $this->assertNotNull($failure);
            $this->assertEquals($this->dungeon->id, $failure->dungeon_id);
            $this->assertGreaterThan(0, $failure->floor_id);
            $this->assertGreaterThan(0, $failure->mapping_version_id);
        } finally {
            if (!empty($insertedIds)) {
                CombatLogRouteEnemyFailure::whereIn('id', $insertedIds)->delete();
            }

            if (!empty($npcEnemyForcesIds)) {
                NpcEnemyForces::query()->whereKey($npcEnemyForcesIds)->delete();
                new NpcEnemyForces()->flushCache();
            }
        }
    }

    /**
     * Only npcs worth enemy forces have their placement failures recorded (#4475), and this preseason fixture's npcs
     * have no enemy forces tuned at all - give every one of them some, so the fixture's unresolvable npcs still land in
     * the table and this test keeps testing what it says it does.
     *
     * @param  array<string, mixed> $postBody
     * @return int[]                the created NpcEnemyForces ids
     */
    private function giveFixtureNpcsEnemyForces(array $postBody): array
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::query()
            ->where('dungeon_id', $this->dungeon->id)
            ->where('version', $postBody['settings']['mappingVersion'])
            ->firstOrFail();

        /** @var int[] $npcIds */
        $npcIds = array_values(array_unique(array_column($postBody['npcs'], 'npcId')));

        $existingNpcIds = NpcEnemyForces::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->whereIn('npc_id', $npcIds)
            ->pluck('npc_id')
            ->all();

        $createdIds = [];
        foreach (array_diff($npcIds, $existingNpcIds) as $npcId) {
            $createdIds[] = NpcEnemyForces::query()->create([
                'mapping_version_id' => $mappingVersion->id,
                'npc_id'             => $npcId,
                'enemy_forces'       => 10,
            ])->id;
        }

        new NpcEnemyForces()->flushCache();

        return $createdIds;
    }
}
