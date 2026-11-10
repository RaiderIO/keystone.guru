<?php

namespace Tests\Feature\Console\Commands\OpenWorldNpc;

use App\Console\Commands\OpenWorldNpc\ExportPortraitBatch;
use App\Models\Npc\OpenWorldNpc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('OpenWorldNpc')]
final class ExportPortraitBatchTest extends PublicTestCase
{
    private string $outputDirectory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Every expectation below counts the whole table; the seeded test database has no open-world NPCs
        $this->assertSame(0, OpenWorldNpc::query()->count(), 'open_world_npcs must start empty; an earlier test leaked rows');

        $this->outputDirectory = sprintf('%s/open-world-npc-portraits-%s', sys_get_temp_dir(), uniqid());
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            OpenWorldNpc::query()->delete();
            File::deleteDirectory($this->outputDirectory);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenNpcsWithoutPortrait_exportsOnlyThoseWithADisplayIdInThePipelinesFormat(): void
    {
        // Arrange
        OpenWorldNpc::factory()->create(['id' => 11, 'display_id' => 111]);
        OpenWorldNpc::factory()->withPortrait()->create(['id' => 12, 'display_id' => 122]);
        OpenWorldNpc::factory()->withoutDisplayId()->create(['id' => 13]);
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 14, 'display_id' => 144]);
        OpenWorldNpc::factory()->create(['id' => 15, 'display_id' => 155]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory])
            ->expectsOutputToContain(sprintf('Exported 2 NPCs to %s', $this->outputDirectory))
            ->expectsOutputToContain('NPC ids: 11, 15')
            ->expectsOutputToContain('The in-game script takes 1 screenshot(s) at 2560x1440')
            ->expectsOutputToContain('0 NPCs remain for later batches; 1 wait for a display id')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame("\"id\"\n\"11\"\n\"15\"\n", File::get(sprintf('%s/npcs.csv', $this->outputDirectory)));
        $this->assertSame("npc_id;display_id\n11;111\n15;155\n", File::get(sprintf('%s/display_ids.csv', $this->outputDirectory)));
        $this->assertSame(
            File::get(base_path('tests/Fixtures/OpenWorldNpc/display_portraits.lua')),
            File::get(sprintf('%s/display_portraits.lua', $this->outputDirectory)),
        );
    }

    #[Test]
    public function handle_givenMoreNpcsThanTheBatchSize_exportsTheLowestIdsAndReportsTheRest(): void
    {
        // Arrange
        OpenWorldNpc::factory()->create(['id' => 23, 'display_id' => 233]);
        OpenWorldNpc::factory()->create(['id' => 21, 'display_id' => 211]);
        OpenWorldNpc::factory()->create(['id' => 22, 'display_id' => 222]);
        OpenWorldNpc::factory()->create(['id' => 24, 'display_id' => 244]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, [
            '--output-dir' => $this->outputDirectory,
            '--batch-size' => 3,
            // Two 64x64 portraits fit on one screenshot at this resolution
            '--resolution' => '128x64',
        ])
            ->expectsOutputToContain('Exported 3 NPCs')
            ->expectsOutputToContain('The in-game script takes 2 screenshot(s) at 128x64')
            ->expectsOutputToContain('1 NPCs remain for later batches')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame("\"id\"\n\"21\"\n\"22\"\n\"23\"\n", File::get(sprintf('%s/npcs.csv', $this->outputDirectory)));
        $lua = File::get(sprintf('%s/display_portraits.lua', $this->outputDirectory));
        $this->assertStringStartsWith("local displayIds = {\n211,\n222,\n233,\n}\n", $lua);
        $this->assertStringContainsString("local resX = 128\nlocal resY = 64\n", $lua);
    }

    #[Test]
    public function handle_givenRetryUnrenderable_alsoExportsNpcsThatCouldNotBeRendered(): void
    {
        // Arrange
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 31, 'display_id' => 311]);
        OpenWorldNpc::factory()->create(['id' => 32, 'display_id' => 322]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--retry-unrenderable' => true])
            ->expectsOutputToContain('NPC ids: 31, 32')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame("npc_id;display_id\n31;311\n32;322\n", File::get(sprintf('%s/display_ids.csv', $this->outputDirectory)));
    }

    #[Test]
    public function handle_givenNoNpcWaitingForAPortrait_writesNothing(): void
    {
        // Arrange
        OpenWorldNpc::factory()->withPortrait()->create(['id' => 41]);
        OpenWorldNpc::factory()->withoutDisplayId()->create(['id' => 42]);
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 43]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory])
            ->expectsOutputToContain('No open-world NPC is waiting for a portrait; 1 wait for a display id')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDirectoryDoesNotExist($this->outputDirectory);
    }

    #[Test]
    public function handle_givenAnEmptyTable_writesNothing(): void
    {
        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory])
            ->expectsOutputToContain('No open-world NPC is waiting for a portrait; 0 wait for a display id')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDirectoryDoesNotExist($this->outputDirectory);
    }

    #[Test]
    public function handle_givenAnInvalidOption_failsAndWritesNothing(): void
    {
        // Arrange
        OpenWorldNpc::factory()->create(['id' => 51, 'display_id' => 511]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--resolution' => '2560'])
            ->expectsOutputToContain('--resolution must look like 2560x1440')
            ->assertExitCode(Command::FAILURE);
        // Smaller than one portrait, so no screenshot would hold any
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--resolution' => '32x1440'])
            ->expectsOutputToContain('--resolution must look like 2560x1440 and be at least 64x64')
            ->assertExitCode(Command::FAILURE);
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--batch-size' => 0])
            ->expectsOutputToContain('--batch-size must be a positive whole number')
            ->assertExitCode(Command::FAILURE);

        $this->assertDirectoryDoesNotExist($this->outputDirectory);
    }
}
