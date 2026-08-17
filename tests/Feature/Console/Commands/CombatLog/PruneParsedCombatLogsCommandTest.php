<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Console\Commands\CombatLog\PruneParsedCombatLogsCommand;
use App\Models\CombatLog\ParsedCombatLog;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class PruneParsedCombatLogsCommandTest extends PublicTestCase
{
    /** @var array<int> */
    private array $createdIds = [];

    private function retentionDays(): int
    {
        $windowDays = (int)config('keystoneguru.raider_io.combat_log_polling.completed_at_window_days');

        return max(30, $windowDays * 4);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            ParsedCombatLog::query()->whereIn('id', $this->createdIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenOldAndRecentParsedCombatLogs_deletesOnlyOldOnes(): void
    {
        // Arrange
        $retentionDays = $this->retentionDays();

        $old = ParsedCombatLog::create([
            'combat_log_path' => 'old/path.log',
            'created_at'      => Carbon::now()->subDays($retentionDays + 1),
            'updated_at'      => Carbon::now()->subDays($retentionDays + 1),
        ]);
        $this->createdIds[] = $old->id;

        $recent = ParsedCombatLog::create([
            'combat_log_path' => 'recent/path.log',
            'created_at'      => Carbon::now()->subDays($retentionDays - 1),
            'updated_at'      => Carbon::now()->subDays($retentionDays - 1),
        ]);
        $this->createdIds[] = $recent->id;

        // Act
        $this->artisan(PruneParsedCombatLogsCommand::class)->assertSuccessful();

        // Assert
        $this->assertSame(0, ParsedCombatLog::query()->where('id', $old->id)->count());
        $this->assertSame(1, ParsedCombatLog::query()->where('id', $recent->id)->count());

        // The command already removed the old row - only the recent one is still ours to clean up
        $this->createdIds = array_filter($this->createdIds, static fn(int $id) => $id === $recent->id);
    }

    #[Test]
    public function handle_givenWidenedPollingWindow_derivesRetentionFromItInsteadOfTheFloor(): void
    {
        // Arrange - window_days=20 pushes retention (20*4=80) past the 30-day floor, so a 40-day-old
        // row - which the floor alone would prune - must survive.
        config(['keystoneguru.raider_io.combat_log_polling.completed_at_window_days' => 20]);

        $survivesOnlyUnderWidenedWindow = ParsedCombatLog::create([
            'combat_log_path' => 'widened-window/path.log',
            'created_at'      => Carbon::now()->subDays(40),
            'updated_at'      => Carbon::now()->subDays(40),
        ]);
        $this->createdIds[] = $survivesOnlyUnderWidenedWindow->id;

        // Act
        $this->artisan(PruneParsedCombatLogsCommand::class)->assertSuccessful();

        // Assert
        $this->assertSame(1, ParsedCombatLog::query()->where('id', $survivesOnlyUnderWidenedWindow->id)->count());
    }
}
