<?php

namespace Tests\Feature\Controller\Api\V1\APICombatLogController\CombatLogRoute;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
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
        return Dungeon::DUNGEON_MAGISTERS_TERRACE_MIDNIGHT;
    }

    #[Test]
    public function store_givenRouteWithUnresolvableNpcs_persistsEnemyFailures(): void
    {
        // Arrange
        $postBody    = $this->getJsonData('Midnight/midnight_s1_magisters_terrace_preseason', self::FIXTURES_ROOT_DIR);
        $countBefore = CombatLogRouteEnemyFailure::where('dungeon_id', $this->dungeon->id)->count();
        $insertedIds = [];

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
        }
    }
}
