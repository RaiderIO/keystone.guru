<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\Dungeon;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsCombatLogControllerTest extends PublicTestCase
{
    #[Test]
    public function combatLogRouteEnemyFailures_givenAdmin_returnsOk(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function combatLogRouteEnemyFailures_givenGuest_redirectsToLogin(): void
    {
        // Act
        $response = $this->get(route('admin.tools.combatlog.route.enemy_failures.view'));

        // Assert
        $response->assertRedirect();
    }

    #[Test]
    public function getEnemyFailures_givenAdmin_returnsDungeonRoutesKey(): void
    {
        // Arrange
        $this->be(User::findOrFail(1));

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->inRandomOrder()->first();

        // Act
        $response = $this->getJson(
            route('ajax.admin.combatlogroute.enemy_failures', ['dungeon_id' => $dungeon->id]),
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        // Assert
        $response->assertOk();
        $response->assertJsonStructure(['data', 'data_type', 'weight_max', 'failure_count', 'grid_size_x', 'grid_size_y', 'dungeon_routes']);
    }
}
