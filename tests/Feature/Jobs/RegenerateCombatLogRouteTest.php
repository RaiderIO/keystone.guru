<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Logging\RegenerateCombatLogRouteLoggingInterface;
use App\Jobs\RegenerateCombatLogRoute;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('CombatLog')]
final class RegenerateCombatLogRouteTest extends PublicTestCase
{
    use ProvidesDungeon;

    /**
     * Without this guard Guzzle sends `Basic Og==` - an empty username and password - and the API answers
     * the very same opaque 401 as a wrong password would, which is what made #4186 undiagnosable.
     */
    #[Test]
    public function handle_givenUnconfiguredCredentials_logsCredentialsNotConfiguredWithoutSendingARequest(): void
    {
        // Arrange
        config()->set('keystoneguru.combat_log_route_regeneration.user', null);
        config()->set('keystoneguru.combat_log_route_regeneration.password', null);

        [$dungeon]    = $this->findDungeon(challengeMode: true);
        $dungeonRoute = $this->createDungeonRouteWithChallengeModeRun($dungeon);

        $endResults = [];
        $log        = $this->createMockPublic(RegenerateCombatLogRouteLoggingInterface::class);
        $log->expects($this->once())->method('handleCredentialsNotConfigured');
        $log->expects($this->never())->method('handleRequestError');
        $log->expects($this->never())->method('handleSuccess');
        $log->method('handleEnd')->willReturnCallback(static function (bool $result) use (&$endResults): void {
            $endResults[] = $result;
        });
        app()->instance(RegenerateCombatLogRouteLoggingInterface::class, $log);

        try {
            // Act
            new RegenerateCombatLogRoute($dungeonRoute->id)->handle();

            // Assert - alongside the expectations set on the mock above
            $this->assertSame([false], $endResults);
        } finally {
            $challengeModeRunIds = ChallengeModeRun::query()->where('dungeon_route_id', $dungeonRoute->id)->pluck('id');
            ChallengeModeRunData::query()->whereIn('challenge_mode_run_id', $challengeModeRunIds)->delete();
            ChallengeModeRun::query()->whereIn('id', $challengeModeRunIds)->delete();
            DungeonRoute::query()->where('id', $dungeonRoute->id)->delete();
        }
    }

    #[Test]
    public function handle_givenUnknownDungeonRoute_logsDungeonRouteNotFound(): void
    {
        // Arrange
        $endResults = [];
        $log        = $this->createMockPublic(RegenerateCombatLogRouteLoggingInterface::class);
        $log->expects($this->once())->method('handleDungeonRouteNotFound');
        $log->expects($this->never())->method('handleCredentialsNotConfigured');
        $log->method('handleEnd')->willReturnCallback(static function (bool $result) use (&$endResults): void {
            $endResults[] = $result;
        });
        app()->instance(RegenerateCombatLogRouteLoggingInterface::class, $log);

        // Act
        new RegenerateCombatLogRoute(PHP_INT_MAX)->handle();

        // Assert - alongside the expectations set on the mock above
        $this->assertSame([false], $endResults);
    }

    private function createDungeonRouteWithChallengeModeRun(Dungeon $dungeon): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
        ]);

        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'       => $dungeon->id,
            'dungeon_route_id' => $dungeonRoute->id,
            'level'            => 10,
            'success'          => 1,
            'total_time_ms'    => 1000,
            'duplicate'        => 0,
        ]);

        ChallengeModeRunData::create([
            'challenge_mode_run_id' => $challengeModeRun->id,
            'run_id'                => 'test - regenerate combat log route',
            'correlation_id'        => 'test-regenerate',
            'post_body'             => '{"settings":{}}',
            'processed'             => 0,
        ]);

        return $dungeonRoute;
    }
}
