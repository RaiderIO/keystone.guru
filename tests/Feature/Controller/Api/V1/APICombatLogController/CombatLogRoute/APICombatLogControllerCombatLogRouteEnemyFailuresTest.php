<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRoute;
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

    /** @var int[] */
    private array $createdNpcEnemyForcesIds = [];

    protected function getDungeonKey(): string
    {
        return DungeonKey::MAGISTERS_TERRACE_MIDNIGHT->value;
    }

    #[Test]
    public function store_givenRouteWithUnresolvableNpcs_persistsEnemyFailures(): void
    {
        try {
            // Arrange
            $postBody = $this->getJsonData('Midnight/midnight_s1_magisters_terrace_preseason', self::FIXTURES_ROOT_DIR);
            $this->giveFixtureNpcsEnemyForces($postBody);

            // Act
            $responseArr = $this->storeCombatLogRoute($postBody);

            // Assert - only the failures recorded for this route, whatever other rows the dungeon carries
            $dungeonRoute = DungeonRoute::where('public_key', $responseArr['data']['publicKey'])->firstOrFail();
            $failures     = CombatLogRouteEnemyFailure::where('dungeon_route_id', $dungeonRoute->id)->get();

            $this->assertNotEmpty($failures, 'Expected at least one CombatLogRouteEnemyFailure to be persisted.');

            /** @var CombatLogRouteEnemyFailure $failure */
            $failure = $failures->first();
            $this->assertEquals($this->dungeon->id, $failure->dungeon_id);
            $this->assertGreaterThan(0, $failure->floor_id);
            $this->assertGreaterThan(0, $failure->mapping_version_id);
        } finally {
            if (!empty($this->createdNpcEnemyForcesIds)) {
                NpcEnemyForces::query()->whereKey($this->createdNpcEnemyForcesIds)->delete();
                new NpcEnemyForces()->flushCache();
            }
        }
    }

    /**
     * Only npcs worth enemy forces have their placement failures recorded (#4475), and this preseason fixture's npcs
     * have no enemy forces tuned at all - give every one of them some, so the fixture's unresolvable npcs still land in
     * the table and this test keeps testing what it says it does.
     *
     * Every created id is recorded as it is created, so a failure part-way through still cleans up what was made.
     *
     * @param array<string, mixed> $postBody
     */
    private function giveFixtureNpcsEnemyForces(array $postBody): void
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

        foreach (array_diff($npcIds, $existingNpcIds) as $npcId) {
            $this->createdNpcEnemyForcesIds[] = NpcEnemyForces::query()->create([
                'mapping_version_id' => $mappingVersion->id,
                'npc_id'             => $npcId,
                'enemy_forces'       => 10,
            ])->id;
        }

        new NpcEnemyForces()->flushCache();
    }
}
