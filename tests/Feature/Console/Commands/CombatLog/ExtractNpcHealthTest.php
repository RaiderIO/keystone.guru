<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Models\Npc\NpcHealth;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('ExtractNpcHealth')]
final class ExtractNpcHealthTest extends PublicTestCase
{
    /** A +7 Freehold run with its CHALLENGE_MODE_START in the file */
    private const string COMBAT_LOG = 'tests/CombatLogs/WoWCombatLog-050923_172619_7_freehold.zip';

    #[Test]
    #[SlowTest]
    public function handle_givenDryRun_printsComparisonAndWritesNothing(): void
    {
        // Arrange
        $healthsBefore = $this->npcHealthsSnapshot();

        // Act
        $exitCode = Artisan::call('combatlog:extractnpchealth', [
            'filePath'  => base_path(self::COMBAT_LOG),
            '--dry-run' => true,
        ]);
        $output = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Run context: key level 7', $output);
        $this->assertStringContainsString('Dungeon: Freehold', $output);
        $this->assertStringContainsString('Dry run - nothing was written', $output);
        // The table lists NPCs of the dungeon with the factor the key level implies for trash at +7
        $this->assertMatchesRegularExpression('/\| 7 +\| 1\.8000 +\|/', $output);
        $this->assertSame($healthsBefore, $this->npcHealthsSnapshot());
    }

    #[Test]
    #[SlowTest]
    public function handle_givenKeyLevelOption_usesItInsteadOfTheLog(): void
    {
        // Act
        $exitCode = Artisan::call('combatlog:extractnpchealth', [
            'filePath'    => base_path(self::COMBAT_LOG),
            '--dry-run'   => true,
            '--key-level' => 4,
            '--affix-ids' => '9,10',
        ]);
        $output = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Run context: key level 4, affix ids [9, 10]', $output);
    }

    #[Test]
    public function handle_givenUnknownGameVersion_fails(): void
    {
        // Act
        $exitCode = Artisan::call('combatlog:extractnpchealth', [
            'filePath'       => base_path(self::COMBAT_LOG),
            '--game-version' => 'does-not-exist',
            '--dry-run'      => true,
        ]);

        // Assert
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unknown game version', Artisan::output());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function npcHealthsSnapshot(): array
    {
        return NpcHealth::query()->orderBy('id')->get(['id', 'npc_id', 'game_version_id', 'health', 'percentage'])->toArray();
    }
}
