<?php

namespace Tests\Feature\App\Model\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
final class CombatLogRouteEnemyResolutionTest extends PublicTestCase
{
    #[Test]
    public function with_givenPersistedResolution_loadsMainDatabaseRelations(): void
    {
        // Arrange
        $resolution = CombatLogRouteEnemyResolution::factory()->create();

        try {
            // Act
            $retrieved = CombatLogRouteEnemyResolution::with(['dungeon', 'floor', 'mappingVersion', 'npc', 'enemy'])
                ->findOrFail($resolution->id);

            // Assert
            $this->assertSame($resolution->dungeon_id, $retrieved->getRelation('dungeon')?->getKey());
            $this->assertSame($resolution->floor_id, $retrieved->getRelation('floor')?->getKey());
            $this->assertSame($resolution->mapping_version_id, $retrieved->getRelation('mappingVersion')?->getKey());
            $this->assertSame($resolution->npc_id, $retrieved->getRelation('npc')?->getKey());
            $this->assertSame($resolution->enemy_id, $retrieved->getRelation('enemy')?->getKey());
        } finally {
            CombatLogRouteEnemyResolution::where('id', $resolution->id)->delete();
        }
    }
}
