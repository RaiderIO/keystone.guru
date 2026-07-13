<?php

namespace Tests\Feature\Controller\AdminTools;

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

        // Act
        $response = $this->post(route('admin.tools.artisancommands.run'), [
            'command' => 'some:dangerous-command',
            'options' => [],
        ]);

        // Assert
        $response->assertStatus(422);
    }
}
