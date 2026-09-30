<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\KillZone\KillZoneEnemy;
use App\Models\Laratrust\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
final class AdminToolsArtisanCommandsControllerTest extends PublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(1));
    }

    #[Test]
    public function backfillKillZoneEnemyId_givenAuthenticatedAdmin_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.artisancommands.backfillkillzoneenemyid.view'));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function backfillKillZoneEnemyId_givenKillZoneEnemyWithoutEnemyId_countsItAndEndsTheRangeAtIt(): void
    {
        // Arrange
        $killZoneEnemy = null;

        try {
            $killZoneEnemy = KillZoneEnemy::factory()->create(['enemy_id' => null]);

            // Act
            $response = $this->get(route('admin.tools.artisancommands.backfillkillzoneenemyid.view'));

            // Assert
            $response->assertOk();
            $response->assertViewHas('count', KillZoneEnemy::query()->whereNull('enemy_id')->count());
            $response->assertViewHas('minId', (int)KillZoneEnemy::query()->whereNull('enemy_id')->min('id'));
            $response->assertViewHas('maxId', $killZoneEnemy->id);
        } finally {
            $killZoneEnemy?->delete();
        }
    }

    #[Test]
    public function run_givenWhitelistedCommand_returnsJsonWithOutput(): void
    {
        // Arrange
        Artisan::shouldReceive('call')->once()->with('ksg:backfill-kill-zone-enemy-id', ['--min' => '1', '--max' => '100'])->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Updated: 100 rows');

        // Act
        $response = $this->post(route('admin.tools.artisancommands.run'), [
            'command' => 'ksg:backfill-kill-zone-enemy-id',
            'options' => ['--min' => '1', '--max' => '100'],
        ]);

        // Assert
        $response->assertOk();
        $response->assertJson(['exit_code' => 0, 'output' => 'Updated: 100 rows']);
    }

    #[Test]
    public function run_givenNonWhitelistedCommand_returns422(): void
    {
        // Arrange
        Artisan::shouldReceive('call')->never();

        // Act
        $response = $this->post(route('admin.tools.artisancommands.run'), [
            'command' => 'some:dangerous-command',
            'options' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Command "some:dangerous-command" is not allowed.');
    }

    #[Test]
    public function run_givenNonAdmin_returnsForbiddenWithoutRunningTheCommand(): void
    {
        // Arrange
        $user = null;
        Artisan::shouldReceive('call')->never();

        try {
            $user = User::factory()->create();
            $user->addRole(Role::firstWhere('name', Role::ROLE_USER));
            $this->be($user);

            // Act
            $response = $this->post(route('admin.tools.artisancommands.run'), [
                'command' => 'ksg:backfill-kill-zone-enemy-id',
                'options' => [],
            ]);

            // Assert
            $response->assertForbidden();
        } finally {
            $user?->delete();
        }
    }
}
