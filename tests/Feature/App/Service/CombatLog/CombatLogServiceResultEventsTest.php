<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Verifies CombatFilter detects enemy kills from real combat logs. PARTY_KILL/UNIT_DIED extend
 * GenericSpecialEvent rather than CombatLogEvent, so a kill-detection branch narrowed to
 * CombatLogEvent silently drops every one of them.
 */
#[Group('CombatLog')]
#[Group('CombatLogServiceResultEvents')]
final class CombatLogServiceResultEventsTest extends PublicTestCase
{
    private const string PIT_OF_SARON_ZIP      = '/CombatLogs/mn_s1/WoWCombatLog-061126_213150_10_pit-of-saron.zip';
    private const string MAGISTERS_TERRACE_ZIP = '/CombatLogs/mn_s1/WoWCombatLog-061126_213150_10_magisters-terrace.zip';

    private const int PIT_OF_SARON_EXPECTED_KILLS      = 171;
    private const int MAGISTERS_TERRACE_EXPECTED_KILLS = 174;

    #[Test]
    public function getResultEventsForChallengeMode_givenPitOfSaronZip_returnsExpectedKillCount(): void
    {
        $zipPath = base_path('tests') . self::PIT_OF_SARON_ZIP;

        if (!file_exists($zipPath)) {
            $this->markTestSkipped('Pit of Saron zip not found');
        }

        // Arrange
        /** @var CombatLogServiceInterface $service */
        $service = app()->make(CombatLogServiceInterface::class);

        // Act
        $resultEvents = $this->getResultEventsForChallengeMode($service, $zipPath);

        // Assert
        $killedCount  = $resultEvents->filter(static fn($e) => $e instanceof EnemyKilled)->count();
        $engagedCount = $resultEvents->filter(static fn($e) => $e instanceof EnemyEngaged)->count();

        $this->assertSame(
            self::PIT_OF_SARON_EXPECTED_KILLS,
            $killedCount,
            sprintf('Expected %d killed enemies, got %d', self::PIT_OF_SARON_EXPECTED_KILLS, $killedCount),
        );
        $this->assertSame(
            $killedCount,
            $engagedCount,
            'Every killed enemy should have a matching engaged event',
        );
    }

    #[Test]
    public function getResultEventsForChallengeMode_givenMagistersTerraceZip_returnsExpectedKillCount(): void
    {
        $zipPath = base_path('tests') . self::MAGISTERS_TERRACE_ZIP;

        if (!file_exists($zipPath)) {
            $this->markTestSkipped("Magister's Terrace zip not found");
        }

        // Arrange
        /** @var CombatLogServiceInterface $service */
        $service = app()->make(CombatLogServiceInterface::class);

        // Act
        $resultEvents = $this->getResultEventsForChallengeMode($service, $zipPath);

        // Assert
        $killedCount  = $resultEvents->filter(static fn($e) => $e instanceof EnemyKilled)->count();
        $engagedCount = $resultEvents->filter(static fn($e) => $e instanceof EnemyEngaged)->count();

        $this->assertSame(
            self::MAGISTERS_TERRACE_EXPECTED_KILLS,
            $killedCount,
            sprintf('Expected %d killed enemies, got %d', self::MAGISTERS_TERRACE_EXPECTED_KILLS, $killedCount),
        );
        $this->assertSame(
            $killedCount,
            $engagedCount,
            'Every killed enemy should have a matching engaged event',
        );
    }

    /**
     * Parsing a challenge mode log saves the dungeon route it builds, so remove whatever it created.
     *
     * @return Collection<int, BaseResultEvent>
     */
    private function getResultEventsForChallengeMode(CombatLogServiceInterface $service, string $zipPath): Collection
    {
        $sinceId = (int)DungeonRoute::query()->max('id');

        try {
            return $service->getResultEventsForChallengeMode($zipPath);
        } finally {
            DungeonRoute::query()->where('id', '>', $sinceId)->get()->each(static fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
        }
    }
}
