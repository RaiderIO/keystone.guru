<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Service\CombatLog\CombatLogServiceInterface;
use App\Service\CombatLog\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\ResultEvents\EnemyKilled;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Regression tests that verify BaseCombatFilter correctly detects enemy kills from real combat logs.
 * These tests guard against the PhpStan regression (commit a3b5cfb47) where the kill-detection
 * else-branch was narrowed to CombatLogEvent, silently dropping PARTY_KILL/UNIT_DIED events that
 * extend GenericSpecialEvent rather than CombatLogEvent.
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
        $resultEvents = $service->getResultEventsForChallengeMode($zipPath);

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
        $resultEvents = $service->getResultEventsForChallengeMode($zipPath);

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
}
