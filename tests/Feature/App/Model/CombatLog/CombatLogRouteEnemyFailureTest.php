<?php

namespace Tests\Feature\App\Model\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Floor\Floor;
use App\Models\Npc\Npc;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
final class CombatLogRouteEnemyFailureTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function create_givenValidData_persistsAndRetrievesRecord(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithNonFacadeFloor();

        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->first();

        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Act
        $failure = CombatLogRouteEnemyFailure::create([
            'dungeon_id'         => $dungeon->id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $mappingVersion->id,
            'npc_id'             => null,
            'lat'                => 12.3456,
            'lng'                => 78.9012,
        ]);

        try {
            // Assert
            $retrieved = CombatLogRouteEnemyFailure::find($failure->id);

            $this->assertNotNull($retrieved);
            $this->assertEquals($dungeon->id, $retrieved->dungeon_id);
            $this->assertEquals($floor->id, $retrieved->floor_id);
            $this->assertEquals($mappingVersion->id, $retrieved->mapping_version_id);
            $this->assertNull($retrieved->npc_id);
            $this->assertEquals(12.3456, $retrieved->lat);
            $this->assertEquals(78.9012, $retrieved->lng);
        } finally {
            CombatLogRouteEnemyFailure::where('id', $failure->id)->delete();
        }
    }

    #[Test]
    public function create_givenKnownNpcId_storesNpcId(): void
    {
        // Arrange
        /** @var Npc $npc */
        $npc = Npc::first();

        $dungeon = $this->getDungeonWithNonFacadeFloor();

        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->first();

        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Act
        $failure = CombatLogRouteEnemyFailure::create([
            'dungeon_id'         => $dungeon->id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $mappingVersion->id,
            'npc_id'             => $npc->id,
            'lat'                => 50.0,
            'lng'                => 50.0,
        ]);

        try {
            // Assert
            $this->assertEquals($npc->id, CombatLogRouteEnemyFailure::find($failure->id)?->npc_id);
        } finally {
            CombatLogRouteEnemyFailure::where('id', $failure->id)->delete();
        }
    }

    #[Test]
    public function relationships_givenModel_returnsCorrectRelationshipInstances(): void
    {
        // Arrange
        $failure = new CombatLogRouteEnemyFailure();

        // Act & Assert
        $this->assertInstanceOf(BelongsTo::class, $failure->dungeon());
        $this->assertInstanceOf(BelongsTo::class, $failure->floor());
        $this->assertInstanceOf(BelongsTo::class, $failure->mappingVersion());
        $this->assertInstanceOf(BelongsTo::class, $failure->npc());
    }
}
