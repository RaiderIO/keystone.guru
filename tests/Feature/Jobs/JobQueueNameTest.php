<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CombatLog\ProcessCombatLogFanout;
use App\Jobs\CombatLog\ProcessCombatLogFromS3;
use App\Jobs\CombatLog\ProcessCombatLogSegments;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Jobs\ProcessRouteFloorThumbnailCustom;
use App\Jobs\RefreshDiscoverCache;
use App\Jobs\RegenerateCombatLogRoute;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Models\Season;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
final class JobQueueNameTest extends PublicTestCase
{
    private const string APP_TYPE = 'teststage';

    /**
     * The queue name must carry the stage (APP_TYPE) exactly once - APP_ENV is 'production' in both AWS stages,
     * so including it produced doubled names such as 'staging-production-thumbnail'. See #3804.
     *
     * @param callable(): object $jobFactory
     */
    #[Test]
    #[DataProvider('queueNameProvider')]
    public function construct_givenAppType_setsQueueWithoutAppEnv(callable $jobFactory, string $expectedQueue): void
    {
        // Arrange
        Config::set('app.type', self::APP_TYPE);
        Config::set('app.env', 'production');

        // Act
        $job = $jobFactory();

        // Assert
        $this->assertSame($expectedQueue, $job->queue);
    }

    /**
     * @return array<string, array{0: callable(): object, 1: string}>
     */
    public static function queueNameProvider(): array
    {
        return [
            'RefreshDiscoverCache' => [
                static fn() => new RefreshDiscoverCache(),
                sprintf('%s-long-running', self::APP_TYPE),
            ],
            'RegenerateCombatLogRoute' => [
                static fn() => new RegenerateCombatLogRoute(1),
                sprintf('%s-long-running', self::APP_TYPE),
            ],
            'ProcessRouteFloorThumbnail' => [
                static fn() => new ProcessRouteFloorThumbnail(new DungeonRoute(), 1),
                sprintf('%s-thumbnail', self::APP_TYPE),
            ],
            'ProcessRouteFloorThumbnailCustom' => [
                static fn() => new ProcessRouteFloorThumbnailCustom(new DungeonRouteThumbnailJob(), new DungeonRoute(), 1),
                sprintf('%s-thumbnail-api', self::APP_TYPE),
            ],
            'ProcessCombatLogSegments' => [
                static fn() => new ProcessCombatLogSegments(new Season(), 1, 1),
                sprintf('%s-cl-process', self::APP_TYPE),
            ],
            'ProcessCombatLogFromS3' => [
                static fn() => new ProcessCombatLogFromS3('bucket', 'path.log.zip', 1),
                sprintf('%s-cl-process', self::APP_TYPE),
            ],
            'ProcessCombatLogFanout' => [
                static fn() => new ProcessCombatLogFanout('bucket', 'path/', 1),
                sprintf('%s-cl-fanout', self::APP_TYPE),
            ],
        ];
    }

    /**
     * Horizon drives local queue processing; if a supervisor watches a queue no job dispatches to, that job type
     * silently stops being processed locally (which is what happened to 'local-combat-log-process' before #3804).
     */
    #[Test]
    #[DataProvider('horizonEnvironmentProvider')]
    public function horizonConfig_givenEnvironment_onlyWatchesQueuesJobsDispatchTo(string $environment): void
    {
        // Arrange
        // 'default' is the connection's fallback queue for jobs that never set $this->queue
        $knownSuffixes = ['default'];
        foreach (self::queueNameProvider() as [1 => $expectedQueue]) {
            $knownSuffixes[] = substr($expectedQueue, strlen(self::APP_TYPE) + 1);
        }

        // Act
        $supervisors = config(sprintf('horizon.environments.%s', $environment));

        // Assert
        $this->assertNotEmpty($supervisors);
        foreach ($supervisors as $supervisorName => $supervisorConfig) {
            foreach ($supervisorConfig['queue'] as $queueName) {
                // Everything past the APP_TYPE prefix must be a queue some job actually dispatches to
                $this->assertContains(
                    substr($queueName, strpos($queueName, '-') + 1),
                    $knownSuffixes,
                    sprintf('Horizon supervisor "%s" watches a queue no job dispatches to', $supervisorName),
                );
            }
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function horizonEnvironmentProvider(): array
    {
        return [
            'production' => ['production'],
            'local'      => ['local'],
        ];
    }
}
