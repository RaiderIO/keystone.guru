<?php

namespace Tests\Feature\Console\Commands\Mapping;

use App\Console\Commands\Mapping\AssignMDTIDs;
use App\Models\Enemy;
use App\Models\Faction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Mapping')]
final class AssignMDTIDsTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function handle_givenEnemiesMissingMdtId_assignsIncrementingIdsPerNpc(): void
    {
        // Arrange
        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floorId        = $dungeon->floors()->where('facade', false)->value('id');

        $npcId          = 999999001;
        $existingEnemy  = $this->createEnemy($mappingVersion->id, $floorId, $npcId, 5);
        $missingEnemyA  = $this->createEnemy($mappingVersion->id, $floorId, $npcId, null);
        $missingEnemyB  = $this->createEnemy($mappingVersion->id, $floorId, $npcId, null);
        $otherNpcEnemy  = $this->createEnemy($mappingVersion->id, $floorId, 999999002, null);

        try {
            // Act
            $this->artisan(AssignMDTIDs::class, ['--dungeon' => $dungeon->key])->assertSuccessful();

            // Assert - the two enemies missing an mdt_id for npc 999999001 get incrementing
            // values continuing from the existing max (5), the pre-existing one is untouched,
            // and a different npc_id gets its own independent sequence starting at 1
            $this->assertEquals(5, $existingEnemy->fresh()->mdt_id);
            $this->assertEquals(6, $missingEnemyA->fresh()->mdt_id);
            $this->assertEquals(7, $missingEnemyB->fresh()->mdt_id);
            $this->assertEquals(1, $otherNpcEnemy->fresh()->mdt_id);
        } finally {
            Enemy::whereIn('id', [$existingEnemy->id, $missingEnemyA->id, $missingEnemyB->id, $otherNpcEnemy->id])->delete();
        }
    }

    #[Test]
    public function handle_givenDungeonOption_onlyProcessesThatDungeon(): void
    {
        // Arrange
        $dungeons = $this->getTwoDungeonsWithNonFacadeFloors();
        [$targetDungeon, $otherDungeon] = $dungeons;

        $targetMappingVersion = $targetDungeon->getCurrentMappingVersion();
        $targetFloorId        = $targetDungeon->floors()->where('facade', false)->value('id');
        $otherMappingVersion  = $otherDungeon->getCurrentMappingVersion();
        $otherFloorId         = $otherDungeon->floors()->where('facade', false)->value('id');

        $targetEnemy = $this->createEnemy($targetMappingVersion->id, $targetFloorId, 999999003, null);
        $otherEnemy  = $this->createEnemy($otherMappingVersion->id, $otherFloorId, 999999004, null);

        try {
            // Act
            $this->artisan(AssignMDTIDs::class, ['--dungeon' => $targetDungeon->key])->assertSuccessful();

            // Assert
            $this->assertNotNull($targetEnemy->fresh()->mdt_id);
            $this->assertNull($otherEnemy->fresh()->mdt_id);
        } finally {
            Enemy::whereIn('id', [$targetEnemy->id, $otherEnemy->id])->delete();
        }
    }

    private function createEnemy(int $mappingVersionId, int $floorId, int $npcId, ?int $mdtId): Enemy
    {
        return Enemy::create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'npc_id'             => $npcId,
            'mdt_id'             => $mdtId,
            'faction'            => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'lat'                => -100.0,
            'lng'                => 100.0,
        ]);
    }

    /**
     * @return array{0: \App\Models\Dungeon, 1: \App\Models\Dungeon}
     */
    private function getTwoDungeonsWithNonFacadeFloors(): array
    {
        $first  = $this->getDungeonWithNonFacadeFloor();
        $second = $this->getDungeonWithNonFacadeFloor(fn($query) => $query->where('id', '!=', $first->id));

        return [$first, $second];
    }
}
