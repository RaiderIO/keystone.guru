<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteDeletingCascadesEnemyFailuresTest extends PublicTestCase
{
    #[Test]
    public function delete_givenRouteWithEnemyFailures_deletesItsOwnFailuresOnly(): void
    {
        // Arrange
        $dungeonRouteIds = [];

        try {
            $dungeonRoute      = DungeonRoute::factory()->create();
            $dungeonRouteIds[] = $dungeonRoute->id;
            $otherDungeonRoute = DungeonRoute::factory()->create();
            $dungeonRouteIds[] = $otherDungeonRoute->id;

            $ownFailureIds = [
                $this->createEnemyFailure($dungeonRoute)->id,
                $this->createEnemyFailure($dungeonRoute)->id,
            ];
            $otherRouteFailureId = $this->createEnemyFailure($otherDungeonRoute)->id;
            // An imported row keeps the remote deployment's route id, which may equal a local route's
            $importedFailureId = $this->createEnemyFailure($dungeonRoute, 'production')->id;

            // Act
            $dungeonRoute->delete();

            // Assert
            $this->assertSame(
                0,
                CombatLogRouteEnemyFailure::query()->whereIn('id', $ownFailureIds)->count(),
                'Deleting a route must delete the enemy failures recorded for it',
            );
            $this->assertTrue(
                CombatLogRouteEnemyFailure::query()->whereKey($otherRouteFailureId)->exists(),
                'Deleting a route must leave the enemy failures of other routes alone',
            );
            $this->assertTrue(
                CombatLogRouteEnemyFailure::query()->whereKey($importedFailureId)->exists(),
                'Deleting a route must leave failures imported from another deployment alone',
            );
        } finally {
            CombatLogRouteEnemyFailure::query()->whereIn('dungeon_route_id', $dungeonRouteIds)->delete();
            foreach ($dungeonRouteIds as $dungeonRouteId) {
                DungeonRoute::find($dungeonRouteId)?->delete();
            }
        }
    }

    private function createEnemyFailure(DungeonRoute $dungeonRoute, ?string $source = null): CombatLogRouteEnemyFailure
    {
        return CombatLogRouteEnemyFailure::create([
            'dungeon_route_id'   => $dungeonRoute->id,
            'source'             => $source,
            'dungeon_id'         => $dungeonRoute->dungeon_id,
            'floor_id'           => $dungeonRoute->dungeon->floors()->firstOrFail()->id,
            'mapping_version_id' => $dungeonRoute->mapping_version_id,
            'npc_id'             => null,
            'lat'                => 1.0,
            'lng'                => 1.0,
        ]);
    }
}
