<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Logging\StructuredLogging;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\ChallengeModeRunData;
use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class IngestCombatLogRouteJsonTest extends PublicTestCase
{
    private const string FIXTURE = __DIR__ . '/../../../Controller/Api/V1/APICombatLogController/Fixtures/TWW/tww_s1_ara_kara_city_of_echoes_3.json';

    #[\Override]
    protected function tearDown(): void
    {
        StructuredLogging::enable();

        parent::tearDown();
    }

    #[Test]
    public function handle_givenPersistentIngestion_ownsTheRouteByTheAdminUserTheCommandLogsIn(): void
    {
        // Arrange
        $maxIdBefore = (int)DungeonRoute::query()->max('id');
        $created     = collect();

        try {
            // Act
            $this->artisan('combatlog:ingestcombatlogroutejson', ['filePath' => self::FIXTURE])->assertExitCode(0);

            // Assert
            $created = DungeonRoute::query()->where('id', '>', $maxIdBefore)->get();
            $this->assertCount(1, $created);
            $this->assertSame(1, $created->first()->author_id);
            $this->assertNull($created->first()->expires_at);
        } finally {
            foreach ($created as $route) {
                $runIds = ChallengeModeRun::where('dungeon_route_id', $route->id)->pluck('id');
                $route->delete();
                ChallengeModeRunData::whereIn('challenge_mode_run_id', $runIds)->delete();
                ChallengeModeRun::whereIn('id', $runIds)->delete();
            }
        }
    }
}
