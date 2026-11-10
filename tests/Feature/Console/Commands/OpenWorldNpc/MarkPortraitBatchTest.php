<?php

namespace Tests\Feature\Console\Commands\OpenWorldNpc;

use App\Console\Commands\OpenWorldNpc\MarkPortraitBatch;
use App\Models\Npc\OpenWorldNpc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\FakesEnemyPortraits;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('OpenWorldNpc')]
final class MarkPortraitBatchTest extends PublicTestCase
{
    use FakesEnemyPortraits;

    private string $batchDirectory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(0, OpenWorldNpc::query()->count(), 'open_world_npcs must start empty; an earlier test leaked rows');

        $this->batchDirectory = sprintf('%s/open-world-npc-portraits-%s', sys_get_temp_dir(), uniqid());
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            OpenWorldNpc::query()->delete();
            File::deleteDirectory($this->batchDirectory);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenAPortraitForEveryNpc_marksThemAsHavingAPortrait(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 11]);
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 12]);
        OpenWorldNpc::factory()->create(['id' => 13]);
        $disk->put('11.png', $this->createPortraitPng());
        $disk->put('12.png', $this->createPortraitPng());
        // Not in the batch, so left alone even though its file exists
        $disk->put('13.png', $this->createPortraitPng());
        $npcsCsv = $this->writeNpcsCsv([11, 12]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv])
            ->expectsOutputToContain('Marked 2 NPCs as having a portrait: 11, 12')
            ->expectsOutputToContain('Could not render 0 NPCs: none')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([11 => [true, false], 12 => [true, false], 13 => [false, false]], $this->getPortraitStates());
        $this->assertTrue($disk->exists('11.png'));
        $this->assertTrue($disk->exists('12.png'));
    }

    #[Test]
    public function handle_givenABlankTile_removesItAndMarksTheNpcUnrenderable(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 21]);
        OpenWorldNpc::factory()->create(['id' => 22]);
        $disk->put('21.png', $this->createBlankPng());
        $disk->put('22.png', $this->createPortraitPng());
        $npcsCsv = $this->writeNpcsCsv([21, 22]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv])
            ->expectsOutputToContain('Marked 1 NPCs as having a portrait: 22')
            ->expectsOutputToContain('Could not render 1 NPCs: 21')
            ->expectsOutputToContain('Removed blank or rejected portrait files of: 21')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([21 => [false, true], 22 => [true, false]], $this->getPortraitStates());
        $this->assertFalse($disk->exists('21.png'));
        $this->assertTrue($disk->exists('22.png'));
    }

    #[Test]
    public function handle_givenAnUnreadableFile_removesItAndMarksTheNpcUnrenderable(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 25]);
        $disk->put('25.png', 'not a png');
        $npcsCsv = $this->writeNpcsCsv([25]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv])
            ->expectsOutputToContain('Could not render 1 NPCs: 25')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([25 => [false, true]], $this->getPortraitStates());
        $this->assertFalse($disk->exists('25.png'));
    }

    #[Test]
    public function handle_givenAMissingFile_marksTheNpcUnrenderable(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 31]);
        OpenWorldNpc::factory()->create(['id' => 32]);
        $disk->put('32.png', $this->createPortraitPng());
        $npcsCsv = $this->writeNpcsCsv([31, 32]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv])
            ->expectsOutputToContain('Could not render 1 NPCs: 31')
            ->expectsOutputToContain('Removed blank or rejected portrait files of: none')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([31 => [false, true], 32 => [true, false]], $this->getPortraitStates());
    }

    #[Test]
    public function handle_givenUnrenderableIds_removesTheirFilesAndMarksThemUnrenderable(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 41]);
        OpenWorldNpc::factory()->create(['id' => 42]);
        OpenWorldNpc::factory()->create(['id' => 43]);
        $disk->put('41.png', $this->createPortraitPng());
        $disk->put('42.png', $this->createPortraitPng());
        $disk->put('43.png', $this->createPortraitPng());
        $npcsCsv = $this->writeNpcsCsv([41, 42, 43]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv, '--unrenderable' => ['41,42']])
            ->expectsOutputToContain('Marked 1 NPCs as having a portrait: 43')
            ->expectsOutputToContain('Could not render 2 NPCs: 41, 42')
            ->expectsOutputToContain('Removed blank or rejected portrait files of: 41, 42')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([41 => [false, true], 42 => [false, true], 43 => [true, false]], $this->getPortraitStates());
        $this->assertFalse($disk->exists('41.png'));
        $this->assertFalse($disk->exists('42.png'));
        $this->assertTrue($disk->exists('43.png'));
    }

    #[Test]
    public function handle_givenAnIdThatIsNoOpenWorldNpc_leavesItsFileAloneAndWarns(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 51]);
        $disk->put('51.png', $this->createPortraitPng());
        $disk->put('52.png', $this->createBlankPng());
        $npcsCsv = $this->writeNpcsCsv([51, 52]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv])
            ->expectsOutputToContain('Marked 1 NPCs as having a portrait: 51')
            ->expectsOutputToContain('Not open-world NPCs, left alone: 52')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([51 => [true, false]], $this->getPortraitStates());
        $this->assertTrue($disk->exists('52.png'));
    }

    #[Test]
    public function handle_givenAnEmptyBatch_marksNothing(): void
    {
        // Arrange
        $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 61]);
        $npcsCsv = $this->writeNpcsCsv([]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv])
            ->expectsOutputToContain('Marked 0 NPCs as having a portrait: none')
            ->expectsOutputToContain('Could not render 0 NPCs: none')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame([61 => [false, false]], $this->getPortraitStates());
    }

    #[Test]
    public function handle_givenNoPortraitDirectory_failsAndChangesNothing(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits(withUnknownPortrait: false);
        OpenWorldNpc::factory()->create(['id' => 71]);
        OpenWorldNpc::factory()->create(['id' => 72]);
        $disk->put('72.png', $this->createPortraitPng());
        $npcsCsv = $this->writeNpcsCsv([71, 72]);

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $npcsCsv])
            ->expectsOutputToContain('holds no unknown.png')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame([71 => [false, false], 72 => [false, false]], $this->getPortraitStates());
    }

    #[Test]
    public function handle_givenAnInvalidBatchFile_failsAndChangesNothing(): void
    {
        // Arrange
        $disk = $this->fakeEnemyPortraits();
        OpenWorldNpc::factory()->create(['id' => 81]);
        $disk->put('81.png', $this->createPortraitPng());
        File::ensureDirectoryExists($this->batchDirectory);
        $withoutHeader = sprintf('%s/without-header.csv', $this->batchDirectory);
        File::put($withoutHeader, "\"81\"\n");
        $withInvalidId = sprintf('%s/invalid-id.csv', $this->batchDirectory);
        File::put($withInvalidId, "\"id\"\n\"81\"\n\"abc\"\n");

        // Act & Assert
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => sprintf('%s/missing.csv', $this->batchDirectory)])
            ->expectsOutputToContain('missing.csv does not exist')
            ->assertExitCode(Command::FAILURE);
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $withoutHeader])
            ->expectsOutputToContain('does not start with an "id" header')
            ->assertExitCode(Command::FAILURE);
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $withInvalidId])
            ->expectsOutputToContain('holds an invalid NPC id')
            ->assertExitCode(Command::FAILURE);
        $this->artisan(MarkPortraitBatch::class, ['npcsCsv' => $this->writeNpcsCsv([81]), '--unrenderable' => ['x1']])
            ->expectsOutputToContain('--unrenderable takes NPC ids, got: x1')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame([81 => [false, false]], $this->getPortraitStates());
    }

    /**
     * Writes an npcs.csv the way the export, and the portrait pipeline's `extract` step, write it.
     *
     * @param array<int, int> $npcIds
     */
    private function writeNpcsCsv(array $npcIds): string
    {
        File::ensureDirectoryExists($this->batchDirectory);
        $path = sprintf('%s/npcs.csv', $this->batchDirectory);
        File::put($path, collect($npcIds)->map(static fn(int $npcId) => sprintf("\"%d\"\n", $npcId))->prepend("\"id\"\n")->implode(''));

        return $path;
    }

    /**
     * @return array<int, array{0: bool, 1: bool}> Per NPC id: has_portrait, portrait_unrenderable
     */
    private function getPortraitStates(): array
    {
        return OpenWorldNpc::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(static fn(OpenWorldNpc $npc) => [$npc->id => [$npc->has_portrait, $npc->portrait_unrenderable]])
            ->all();
    }
}
