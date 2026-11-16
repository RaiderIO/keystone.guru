<?php

namespace Tests\Feature\Console\Commands\OpenWorldNpc;

use App\Console\Commands\OpenWorldNpc\ExportPortraitBatch;
use App\Models\Npc\OpenWorldNpc;
use App\Service\OpenWorldNpc\OpenWorldNpcPortraitService;
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
    public function handle_givenNpcsWithoutPortrait_exportsThemByNpcIdWhetherOrNotTheyHaveADisplayId(): void
    {
        // Arrange
        OpenWorldNpc::factory()->create(['id' => 11, 'display_id' => 111]);
        OpenWorldNpc::factory()->withPortrait()->create(['id' => 12, 'display_id' => 122]);
        OpenWorldNpc::factory()->withoutDisplayId()->create(['id' => 13]);
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 14, 'display_id' => 144]);
        OpenWorldNpc::factory()->create(['id' => 15, 'display_id' => 155]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory])
            ->expectsOutputToContain(sprintf('Exported 3 NPCs in 1 batches to %s: batch_01 to batch_01', $this->outputDirectory))
            ->expectsOutputToContain('Each script takes one screenshot at 2560x1440')
            ->expectsOutputToContain('0 NPCs remain for a later export')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame("\"id\"\n\"11\"\n\"13\"\n\"15\"\n", File::get(sprintf('%s/batch_01/npcs.csv', $this->outputDirectory)));
        $this->assertSame(
            strtr(File::get(resource_path(OpenWorldNpcPortraitService::NPC_PORTRAITS_LUA_TEMPLATE)), [
                '{ids_block}'   => "11,\n13,\n15,\n",
                '{batch}'       => '01',
                '{batch_count}' => '01',
                '{res_x}'       => '2560',
                '{res_y}'       => '1440',
            ]),
            File::get(sprintf('%s/batch_01/npc_portraits.lua', $this->outputDirectory)),
        );
        $this->assertFileDoesNotExist(sprintf('%s/batch_01/display_ids.csv', $this->outputDirectory));
    }

    #[Test]
    public function handle_givenMoreNpcsThanTheBatchesHold_exportsTheLowestIdsInNumberedBatchesAndReportsTheRest(): void
    {
        // Arrange
        foreach ([25, 21, 23, 22, 26, 24] as $npcId) {
            OpenWorldNpc::factory()->create(['id' => $npcId]);
        }

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, [
            '--output-dir' => $this->outputDirectory,
            '--batches'    => 2,
            '--batch-size' => 2,
            // Two 64x64 portraits fit on one screenshot at this resolution
            '--resolution' => '128x64',
        ])
            ->expectsOutputToContain('Exported 4 NPCs in 2 batches')
            ->expectsOutputToContain('Each script takes one screenshot at 128x64')
            ->expectsOutputToContain('2 NPCs remain for a later export')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame("\"id\"\n\"21\"\n\"22\"\n", File::get(sprintf('%s/batch_01/npcs.csv', $this->outputDirectory)));
        $this->assertSame("\"id\"\n\"23\"\n\"24\"\n", File::get(sprintf('%s/batch_02/npcs.csv', $this->outputDirectory)));
        $this->assertDirectoryDoesNotExist(sprintf('%s/batch_03', $this->outputDirectory));
        $lua = File::get(sprintf('%s/batch_02/npc_portraits.lua', $this->outputDirectory));
        $this->assertStringStartsWith("-- keystone.guru open-world NPC portraits, batch 02 of 02\nlocal npcIds = {\n23,\n24,\n}\n", $lua);
        $this->assertStringContainsString('local batchLabel = "BATCH 02"', $lua);
        $this->assertStringContainsString("local resX = 128\nlocal resY = 64\n", $lua);
    }

    #[Test]
    public function handle_givenFewerNpcsThanTheBatchesHold_writesOnlyTheBatchesItNeeds(): void
    {
        // Arrange
        foreach ([31, 32, 33] as $npcId) {
            OpenWorldNpc::factory()->create(['id' => $npcId]);
        }

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--batch-size' => 2])
            ->expectsOutputToContain('Exported 3 NPCs in 2 batches')
            ->expectsOutputToContain('0 NPCs remain for a later export')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame("\"id\"\n\"33\"\n", File::get(sprintf('%s/batch_02/npcs.csv', $this->outputDirectory)));
        $this->assertStringStartsWith(
            "-- keystone.guru open-world NPC portraits, batch 02 of 02\n",
            File::get(sprintf('%s/batch_02/npc_portraits.lua', $this->outputDirectory)),
        );
        $this->assertDirectoryDoesNotExist(sprintf('%s/batch_03', $this->outputDirectory));
    }

    #[Test]
    public function handle_givenRetryUnrenderable_alsoExportsNpcsThatCouldNotBeRendered(): void
    {
        // Arrange
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 31, 'display_id' => 311]);
        OpenWorldNpc::factory()->create(['id' => 32, 'display_id' => 322]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--retry-unrenderable' => true])
            ->expectsOutputToContain('Exported 2 NPCs in 1 batches')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame("\"id\"\n\"31\"\n\"32\"\n", File::get(sprintf('%s/batch_01/npcs.csv', $this->outputDirectory)));
    }

    #[Test]
    public function handle_givenNoNpcWaitingForAPortrait_writesNothing(): void
    {
        // Arrange
        OpenWorldNpc::factory()->withPortrait()->create(['id' => 41]);
        OpenWorldNpc::factory()->withUnrenderablePortrait()->create(['id' => 43]);

        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory])
            ->expectsOutputToContain('No open-world NPC is waiting for a portrait')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDirectoryDoesNotExist($this->outputDirectory);
    }

    #[Test]
    public function handle_givenAnEmptyTable_writesNothing(): void
    {
        // Act & Assert
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory])
            ->expectsOutputToContain('No open-world NPC is waiting for a portrait')
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
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--batches' => 0])
            ->expectsOutputToContain('--batches must be a positive whole number')
            ->assertExitCode(Command::FAILURE);
        // Three portraits do not fit on one 128x64 screenshot
        $this->artisan(ExportPortraitBatch::class, ['--output-dir' => $this->outputDirectory, '--batch-size' => 3, '--resolution' => '128x64'])
            ->expectsOutputToContain('--batch-size must be at most 2, the portraits one screenshot at 128x64 holds')
            ->assertExitCode(Command::FAILURE);

        $this->assertDirectoryDoesNotExist($this->outputDirectory);
    }
}
