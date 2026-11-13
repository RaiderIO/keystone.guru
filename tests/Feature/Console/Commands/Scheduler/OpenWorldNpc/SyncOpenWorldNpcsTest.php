<?php

namespace Tests\Feature\Console\Commands\Scheduler\OpenWorldNpc;

use App\Console\Commands\Scheduler\OpenWorldNpc\SyncOpenWorldNpcs;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\OpenWorldNpc;
use App\Service\OpenWorldNpc\Logging\OpenWorldNpcSyncServiceLoggingInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\Traits\CreatesNpc;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Scheduler')]
#[Group('OpenWorldNpc')]
final class SyncOpenWorldNpcsTest extends PublicTestCase
{
    use CreatesNpc;

    private const string DISK      = 's3_open_world_npcs';
    private const string FILE_PATH = 'open-world/deadly-npcs.json';

    private const int NEW_NPC_ID      = 990_000_001;
    private const int EXISTING_NPC_ID = 990_000_002;
    private const int ABSENT_NPC_ID   = 990_000_003;

    /** @var array<int, int> */
    private array $openWorldNpcIdsBeforeTheTest;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->openWorldNpcIdsBeforeTheTest = OpenWorldNpc::query()->pluck('id')->all();

        config()->set(sprintf('filesystems.disks.%s.bucket', self::DISK), 'test-bucket');
        config()->set('keystoneguru.raider_io.open_world_npcs.file_path', self::FILE_PATH);
        Storage::fake(self::DISK);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            // Every open-world NPC the command under test or the arrange step wrote
            OpenWorldNpc::query()->whereNotIn('id', $this->openWorldNpcIdsBeforeTheTest)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenANewNpc_createsItWithItsDisplayIdAndReportsIt(): void
    {
        // Arrange
        $this->putFile([['npcId' => self::NEW_NPC_ID, 'name' => 'Hogger']]);
        $wowheadService = $this->mockWowhead();
        $wowheadService->expects($this->once())
            ->method('getNpcDisplayIdByNpcId')
            ->with($this->isInstanceOf(GameVersion::class), self::NEW_NPC_ID, 'Hogger')
            ->willReturn(4321);
        $expectedWithoutPortrait = OpenWorldNpc::query()->where('has_portrait', false)->pluck('id')->push(self::NEW_NPC_ID)->sort()->values();

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('1 new, 0 renamed, 0 unchanged, 0 skipped')
            ->expectsOutputToContain(sprintf('New NPC ids: %d', self::NEW_NPC_ID))
            ->expectsOutputToContain(sprintf(
                'NPC ids still without a portrait (%d): %s',
                $expectedWithoutPortrait->count(),
                $expectedWithoutPortrait->implode(', '),
            ))
            ->assertExitCode(Command::SUCCESS);

        $openWorldNpc = OpenWorldNpc::query()->find(self::NEW_NPC_ID);
        $this->assertNotNull($openWorldNpc);
        $this->assertSame('Hogger', $openWorldNpc->name);
        $this->assertSame(4321, $openWorldNpc->display_id);
        $this->assertFalse($openWorldNpc->has_portrait);
    }

    #[Test]
    public function handle_givenAKnownNpcWithANewName_renamesItAndKeepsItsDisplayIdAndPortrait(): void
    {
        // Arrange
        OpenWorldNpc::factory()->withPortrait()->create(['id' => self::EXISTING_NPC_ID, 'name' => 'Old Name', 'display_id' => 1234]);
        $this->putFile([['npcId' => self::EXISTING_NPC_ID, 'name' => 'New Name']]);
        $wowheadService = $this->mockWowhead();
        $wowheadService->expects($this->never())->method('getNpcDisplayIdByNpcId');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('0 new, 1 renamed, 0 unchanged, 0 skipped')
            ->expectsOutputToContain('New NPC ids: none')
            ->assertExitCode(Command::SUCCESS);

        $openWorldNpc = OpenWorldNpc::query()->findOrFail(self::EXISTING_NPC_ID);
        $this->assertSame('New Name', $openWorldNpc->name);
        $this->assertSame(1234, $openWorldNpc->display_id);
        $this->assertTrue($openWorldNpc->has_portrait);
    }

    #[Test]
    public function handle_givenAKnownNpcMissingFromTheFile_keepsItsRow(): void
    {
        // Arrange
        OpenWorldNpc::factory()->withPortrait()->create(['id' => self::ABSENT_NPC_ID, 'name' => 'Gone Today', 'display_id' => 1234]);
        $this->putFile([['npcId' => self::NEW_NPC_ID, 'name' => 'Hogger']]);
        $this->mockWowhead()->method('getNpcDisplayIdByNpcId')->willReturn(4321);

        // Act
        $this->artisan(SyncOpenWorldNpcs::class)->assertExitCode(Command::SUCCESS);

        // Assert
        $this->assertTrue(OpenWorldNpc::query()->whereKey(self::NEW_NPC_ID)->exists());
        $openWorldNpc = OpenWorldNpc::query()->find(self::ABSENT_NPC_ID);
        $this->assertNotNull($openWorldNpc);
        $this->assertSame('Gone Today', $openWorldNpc->name);
        $this->assertTrue($openWorldNpc->has_portrait);
    }

    #[Test]
    public function handle_givenAnNpcThatExistsInNpcs_skipsIt(): void
    {
        // Arrange
        $npc = $this->createNpcInDatabase();
        $this->putFile([
            ['npcId' => $npc->id, 'name' => 'Seeded Npc'],
            ['npcId' => self::NEW_NPC_ID, 'name' => 'Hogger'],
        ]);
        $wowheadService = $this->mockWowhead();
        $wowheadService->expects($this->once())
            ->method('getNpcDisplayIdByNpcId')
            ->with($this->anything(), self::NEW_NPC_ID, 'Hogger')
            ->willReturn(4321);

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('Read 2 NPCs (0 invalid entries): 1 new, 0 renamed, 0 unchanged, 1 skipped')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFalse(OpenWorldNpc::query()->whereKey($npc->id)->exists());
        $this->assertTrue(OpenWorldNpc::query()->whereKey(self::NEW_NPC_ID)->exists());
    }

    #[Test]
    public function handle_givenNoDisplayIdOnWowhead_leavesItEmptyAndRetriesOnTheNextRun(): void
    {
        // Arrange
        $this->putFile([['npcId' => self::NEW_NPC_ID, 'name' => 'Hogger']]);
        $wowheadService = $this->mockWowhead();
        $wowheadService->expects($this->exactly(2))
            ->method('getNpcDisplayIdByNpcId')
            ->willReturnOnConsecutiveCalls(null, 4321);

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain(sprintf('Display ids found: 0; still without a display id: %d', self::NEW_NPC_ID))
            ->assertExitCode(Command::SUCCESS);
        $this->assertTrue(OpenWorldNpc::query()->whereKey(self::NEW_NPC_ID)->whereNull('display_id')->exists());

        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('Display ids found: 1; still without a display id: none')
            ->assertExitCode(Command::SUCCESS);
        $this->assertSame(4321, OpenWorldNpc::query()->find(self::NEW_NPC_ID)?->display_id);
    }

    #[Test]
    public function handle_givenMoreNpcsWithoutDisplayIdThanTheBudget_looksUpOnlyTheBudgetAndReportsTheRest(): void
    {
        // Arrange
        config()->set('keystoneguru.raider_io.open_world_npcs.display_id_lookups_per_run', 1);
        $this->putFile([
            ['npcId' => self::NEW_NPC_ID, 'name' => 'Hogger'],
            ['npcId' => self::EXISTING_NPC_ID, 'name' => 'Mor\'Ladim'],
        ]);
        $wowheadService = $this->mockWowhead();
        $wowheadService->expects($this->once())
            ->method('getNpcDisplayIdByNpcId')
            ->with($this->anything(), self::NEW_NPC_ID, 'Hogger')
            ->willReturn(4321);

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain(sprintf('Display ids found: 1; still without a display id: %d', self::EXISTING_NPC_ID))
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(4321, OpenWorldNpc::query()->find(self::NEW_NPC_ID)?->display_id);
        $this->assertTrue(OpenWorldNpc::query()->whereKey(self::EXISTING_NPC_ID)->whereNull('display_id')->exists());
    }

    #[Test]
    public function handle_givenTheSameFileTwice_changesNothingTheSecondTime(): void
    {
        // Arrange
        OpenWorldNpc::factory()->create(['id' => self::EXISTING_NPC_ID, 'name' => 'Old Name', 'display_id' => 1234]);
        $this->putFile([
            ['npcId' => self::NEW_NPC_ID, 'name' => 'Hogger'],
            ['npcId' => self::EXISTING_NPC_ID, 'name' => 'New Name'],
        ]);
        $wowheadService = $this->mockWowhead();
        $wowheadService->expects($this->once())->method('getNpcDisplayIdByNpcId')->willReturn(4321);

        $this->artisan(SyncOpenWorldNpcs::class)->assertExitCode(Command::SUCCESS);
        // Backdated, so that any write by the second run shows in updated_at even within the same second
        OpenWorldNpc::query()->whereIn('id', [self::NEW_NPC_ID, self::EXISTING_NPC_ID])->toBase()->update(['updated_at' => '2020-01-01 00:00:00']);
        $rowsAfterFirstRun = $this->getTestRows();

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('0 new, 0 renamed, 2 unchanged, 0 skipped')
            ->assertExitCode(Command::SUCCESS);

        $this->assertCount(2, $rowsAfterFirstRun);
        $this->assertSame($rowsAfterFirstRun, $this->getTestRows());
    }

    #[Test]
    public function handle_givenAnEmptyList_changesNothingAndSucceeds(): void
    {
        // Arrange
        OpenWorldNpc::factory()->create(['id' => self::EXISTING_NPC_ID, 'name' => 'Old Name', 'display_id' => 1234]);
        $rowsBefore = $this->getTestRows();
        $this->putFile([]);
        $this->mockWowhead()->expects($this->never())->method('getNpcDisplayIdByNpcId');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('Read 0 NPCs (0 invalid entries): 0 new, 0 renamed, 0 unchanged, 0 skipped')
            ->assertExitCode(Command::SUCCESS);

        $this->assertCount(1, $rowsBefore);
        $this->assertSame($rowsBefore, $this->getTestRows());
    }

    #[Test]
    public function handle_givenAMalformedFile_failsAndChangesNothing(): void
    {
        // Arrange
        OpenWorldNpc::factory()->create(['id' => self::EXISTING_NPC_ID, 'name' => 'Old Name', 'display_id' => 1234]);
        $rowsBefore = $this->getTestRows();
        Storage::disk(self::DISK)->put(self::FILE_PATH, sprintf('[{"npcId": %d, "name": "New Name"}', self::EXISTING_NPC_ID));
        $this->mockWowhead()->expects($this->never())->method('getNpcDisplayIdByNpcId');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('Unable to read the open-world NPC list: The file is not valid JSON')
            ->assertExitCode(Command::FAILURE);

        $this->assertCount(1, $rowsBefore);
        $this->assertSame($rowsBefore, $this->getTestRows());
    }

    #[Test]
    public function handle_givenAZeroByteFile_fails(): void
    {
        // Arrange
        Storage::disk(self::DISK)->put(self::FILE_PATH, '');
        $this->mockWowhead()->expects($this->never())->method('getNpcDisplayIdByNpcId');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('Unable to read the open-world NPC list: The file is not valid JSON')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function handle_givenNoFile_fails(): void
    {
        // Arrange
        $this->mockWowhead()->expects($this->never())->method('getNpcDisplayIdByNpcId');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain(sprintf('Unable to read the open-world NPC list: No file at %s', self::FILE_PATH))
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function handle_givenNoBucketConfigured_skipsWithoutReadingTheFile(): void
    {
        // Arrange
        config()->set(sprintf('filesystems.disks.%s.bucket', self::DISK), null);
        $this->putFile([['npcId' => self::NEW_NPC_ID, 'name' => 'Hogger']]);
        $this->mockWowhead()->expects($this->never())->method('getNpcDisplayIdByNpcId');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)
            ->expectsOutputToContain('No bucket is configured for the open-world NPC list; skipping')
            ->assertExitCode(Command::SUCCESS);

        $this->assertFalse(OpenWorldNpc::query()->whereKey(self::NEW_NPC_ID)->exists());
    }

    #[Test]
    public function handle_givenASuccessfulSync_logsTheSyncStartWithItsFileAndTheSyncEnd(): void
    {
        // Arrange
        $this->putFile([]);
        $this->mockWowhead();
        $log = $this->mockLogging();
        $log->expects($this->once())->method('syncStart')->with(self::DISK, self::FILE_PATH);
        $log->expects($this->once())->method('syncResult');
        $log->expects($this->once())->method('syncEnd');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)->assertExitCode(Command::SUCCESS);
    }

    #[Test]
    public function handle_givenNoFile_stillLogsTheSyncEnd(): void
    {
        // Arrange
        $this->mockWowhead();
        $log = $this->mockLogging();
        $log->expects($this->once())->method('syncStart')->with(self::DISK, self::FILE_PATH);
        $log->expects($this->once())->method('syncFileNotFound');
        $log->expects($this->once())->method('syncEnd');

        // Act & Assert
        $this->artisan(SyncOpenWorldNpcs::class)->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function schedule_givenAnyEnvironment_runsDailyInTheBackground(): void
    {
        // Arrange
        $schedule = app()->make(Schedule::class);

        // Act
        $events = collect($schedule->events())
            ->filter(static fn(Event $event) => str_contains((string)$event->command, 'openworldnpc:sync'));

        // Assert
        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame('0 6 * * *', $event->expression);
        $this->assertTrue($event->runInBackground);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->onOneServer);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function putFile(array $entries): void
    {
        Storage::disk(self::DISK)->put(self::FILE_PATH, (string)json_encode($entries));
    }

    private function mockWowhead(): MockObject&WowheadServiceInterface
    {
        $wowheadService = $this->createMockPublic(WowheadServiceInterface::class);
        $this->app->instance(WowheadServiceInterface::class, $wowheadService);

        /** @var MockObject&WowheadServiceInterface $wowheadService */
        return $wowheadService;
    }

    private function mockLogging(): MockObject&OpenWorldNpcSyncServiceLoggingInterface
    {
        $log = $this->createMockPublic(OpenWorldNpcSyncServiceLoggingInterface::class);
        $this->app->instance(OpenWorldNpcSyncServiceLoggingInterface::class, $log);

        /** @var MockObject&OpenWorldNpcSyncServiceLoggingInterface $log */
        return $log;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTestRows(): array
    {
        return OpenWorldNpc::query()
            ->whereIn('id', [self::NEW_NPC_ID, self::EXISTING_NPC_ID, self::ABSENT_NPC_ID])
            ->orderBy('id')
            ->get()
            ->map(static fn(OpenWorldNpc $openWorldNpc) => $openWorldNpc->getAttributes())
            ->all();
    }
}
