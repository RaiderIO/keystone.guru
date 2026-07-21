<?php

namespace Tests\Feature\App\Service\LiveSession;

use App\Models\Dungeon;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Service\LiveSession\LiveSessionCombatLogServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('LiveSession')]
#[Group('LiveSessionCombatLogService')]
final class LiveSessionCombatLogServiceTest extends PublicTestCase
{
    private const string PIT_OF_SARON_EVENTS_FILE = '/CombatLogs/mn_s1/WoWCombatLog-061126_213150_10_pit-of-saron_events.txt';

    private const int PIT_OF_SARON_CHALLENGE_MODE_ID = 556;

    #[Test]
    public function reduceCombatLogForBuffer_givenSpamHeavyLog_dropsTheSpam(): void
    {
        if (!file_exists(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE)) {
            $this->markTestSkipped('Pit of Saron events file not found');
        }

        // Arrange
        $dungeon        = Dungeon::where('challenge_mode_id', self::PIT_OF_SARON_CHALLENGE_MODE_ID)->firstOrFail();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        if ($mappingVersion === null) {
            $this->markTestSkipped('No current mapping version for Pit of Saron');
        }

        $validNpcIds = app(NpcRepositoryInterface::class)->getInUseNpcIds($mappingVersion);
        /** @var LiveSessionCombatLogServiceInterface $service */
        $service = app(LiveSessionCombatLogServiceInterface::class);

        // The fixture is already a curated "relevant events" log; inject realistic spam (repeated damage
        // to an already-sighted enemy) so there is something for the reducer to actually drop.
        $relevantLines = file(base_path('tests') . self::PIT_OF_SARON_EVENTS_FILE, FILE_IGNORE_NEW_LINES);
        $spamTemplate  = collect($relevantLines)->first(static fn(string $line): bool => str_contains($line, 'SPELL_DAMAGE'));
        $this->assertNotNull($spamTemplate);

        $spamCount = 5000;
        $spamLog   = array_merge($relevantLines, array_fill(0, $spamCount, $spamTemplate));

        $tmpFile = tempnam(sys_get_temp_dir(), 'reduce_test_');
        file_put_contents($tmpFile, implode("\n", $spamLog));

        try {
            // Act
            $reduced = $service->reduceCombatLogForBuffer($tmpFile, $validNpcIds);

            // Assert: the 5000 spam lines are gone, leaving roughly the original relevant set
            $this->assertGreaterThan(0, count($reduced));
            $this->assertLessThan(count($spamLog) / 2, count($reduced), 'Expected the reducer to drop the injected spam');
            $this->assertLessThan(count($relevantLines) + 100, count($reduced), 'Expected the reduced output to be close to the relevant set');
        } finally {
            @unlink($tmpFile);
        }
    }
}
