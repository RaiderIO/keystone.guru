<?php

namespace Tests\Feature\App\Models\KillZone;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('KillZone')]
final class KillZoneSkippableEnemyForcesTest extends PublicTestCase
{
    use CreatesDungeon;
    use CreatesNpc;

    #[Test]
    public function getSkippableEnemyForces_givenPullWithASkippableEnemy_returnsItsEnemyIdAndForces(): void
    {
        $enemy          = null;
        $npcEnemyForces = null;
        $dungeonRoute   = null;

        try {
            // Arrange
            $dungeon = $this->createDungeon();
            /** @var MappingVersion $mappingVersion */
            $mappingVersion = $dungeon->mappingVersions()->firstOrFail();
            /** @var Floor $floor */
            $floor = $dungeon->floors()->firstOrFail();
            $npc   = $this->createNpcInDatabase();

            $enemy = Enemy::query()->create([
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $floor->id,
                'npc_id'             => $npc->id,
                'mdt_id'             => 1,
                'faction'            => 'any',
                'required'           => false,
                'skippable'          => true,
                'hyper_respawn'      => false,
                'lat'                => -100,
                'lng'                => 100,
            ]);
            $npcEnemyForces = NpcEnemyForces::query()->create([
                'mapping_version_id' => $mappingVersion->id,
                'npc_id'             => $npc->id,
                'enemy_forces'       => 7,
            ]);
            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
                'teeming'            => false,
            ]);
            /** @var KillZone $killZone */
            $killZone = KillZone::factory()->withEnemies($enemy)->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => null,
                'lat'              => null,
                'lng'              => null,
            ]);

            // Act
            $skippableEnemyForces = $killZone->getSkippableEnemyForces(false);

            // Assert
            $this->assertCount(1, $skippableEnemyForces);
            $this->assertSame($enemy->id, (int)$skippableEnemyForces->first()->enemy_id);
            $this->assertSame(7, (int)$skippableEnemyForces->first()->enemy_forces);
        } finally {
            $dungeonRoute?->delete();
            $npcEnemyForces?->delete();
            $enemy?->delete();
        }
    }
}
