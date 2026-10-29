<?php

namespace Tests\Feature\Console\Commands\Scheduler\WagoTools;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\SpellDescriptionImportState;
use App\Service\Spell\Description\SpellDescriptionPatchCheckServiceInterface;
use App\Service\WagoTools\WagoToolsServiceInterface;
use GrahamCampbell\GitHub\Facades\GitHub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\TestCases\PublicTestCase;
use Tests\Traits\RestoresSpellDescriptionImportState;

#[Group('SpellDescription')]
final class CheckForSpellDescriptionPatchTest extends PublicTestCase
{
    use RestoresSpellDescriptionImportState;

    private const int GAME_VERSION_ID = GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->captureSpellDescriptionImportState(self::GAME_VERSION_ID);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreSpellDescriptionImportState();

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenBuildUnchanged_filesNoIssue(): void
    {
        // Arrange
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69214');

        // GitHub is never touched when nothing changed
        GitHub::shouldReceive('repo->contents->download')->never();
        GitHub::shouldReceive('issue->all')->never();
        GitHub::shouldReceive('issue->create')->never();

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenNewBuildAndNoOpenIssue_filesAReminderIssue(): void
    {
        // Arrange
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69300');
        $this->mockCommittedImportState('12.1.0.69214');

        GitHub::shouldReceive('issue->all')
            ->once()
            ->withArgs(function (string $owner, string $repository, array $params): bool {
                $this->assertSame('RaiderIO', $owner);
                $this->assertSame('Keystone.guru', $repository);
                $this->assertSame('open', $params['state']);

                return true;
            })
            ->andReturn([['number' => 111, 'title' => 'Unrelated open issue']]);

        GitHub::shouldReceive('issue->create')
            ->once()
            ->withArgs(function (string $owner, string $repository, array $params): bool {
                $this->assertSame('RaiderIO', $owner);
                $this->assertSame('Keystone.guru', $repository);
                $this->assertStringContainsString('12.1.0.69300', $params['title']);
                $this->assertStringContainsString('wagotools:importspelldescriptions', $params['body']);

                return true;
            })
            ->andReturn(['number' => 4321]);

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenNewBuildAndAnOpenIssueAlreadyExists_doesNotFileADuplicate(): void
    {
        // Arrange
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69300');
        $this->mockCommittedImportState('12.1.0.69214');

        GitHub::shouldReceive('issue->all')
            ->once()
            ->andReturn([['number' => 4321, 'title' => 'Patch 12.1.0.69300 is out - re-run the spell description import']]);

        GitHub::shouldReceive('issue->create')->never();

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenWagoToolsUnreachable_filesNoIssue(): void
    {
        // Arrange
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild(null);

        GitHub::shouldReceive('issue->all')->never();
        GitHub::shouldReceive('issue->create')->never();

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenGithubUnreachable_filesNoIssueAndDoesNotFail(): void
    {
        // Arrange - a transport failure (DNS, connect timeout) does not implement Github's own exception
        // interface, so this guards the catch is broad enough to still fail closed
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69300');
        $this->mockCommittedImportState('12.1.0.69214');

        GitHub::shouldReceive('issue->all')->once()->andThrow(new RuntimeException('Could not resolve host'));
        GitHub::shouldReceive('issue->create')->never();

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenNoBuildEverRecorded_filesAReminderIssue(): void
    {
        // Arrange - nothing recorded yet (e.g. before this feature's first real import); the reminder
        // itself is what prompts the human to run the import that establishes a baseline
        $this->clearImportState();
        $this->mockLatestBuild('12.1.0.69300');
        $this->mockCommittedImportState(null);

        GitHub::shouldReceive('issue->all')
            ->once()
            ->andReturn([]);

        GitHub::shouldReceive('issue->create')
            ->once()
            ->andReturn(['number' => 4321]);

        // Act & Assert
        $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenLatestBuildAlreadyCommittedOnTheDefaultBranch_filesNoIssue(): void
    {
        // Arrange - the import landed on master but no release has seeded the state table yet, so the
        // table still names the build before it
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69300');
        $this->mockCommittedImportState('12.1.0.69300');

        GitHub::shouldReceive('issue->all')->never();
        GitHub::shouldReceive('issue->create')->never();

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenCommittedStateOlderThanTheLatestBuild_filesAReminderIssueNamingTheCommittedBuild(): void
    {
        // Arrange - the committed state is ahead of the table but still behind the game, so the reminder
        // is genuine and reports the committed build as the one to diff against
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69300');
        $this->mockCommittedImportState('12.1.0.69280');

        GitHub::shouldReceive('issue->all')->once()->andReturn([]);

        GitHub::shouldReceive('issue->create')
            ->once()
            ->withArgs(function (string $owner, string $repository, array $params): bool {
                $this->assertStringContainsString('12.1.0.69300', $params['title']);
                $this->assertStringContainsString('previously imported: `12.1.0.69280`', $params['body']);

                return true;
            })
            ->andReturn(['number' => 4321]);

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenCommittedStateUnreadable_fallsBackToTheStateTableAndFilesAReminderIssue(): void
    {
        // Arrange - fails open: an unreadable file must not suppress a genuine reminder
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69300');

        GitHub::shouldReceive('repo->contents->download')
            ->once()
            ->andThrow(new RuntimeException('Not Found'));

        GitHub::shouldReceive('issue->all')->once()->andReturn([]);

        GitHub::shouldReceive('issue->create')
            ->once()
            ->withArgs(function (string $owner, string $repository, array $params): bool {
                $this->assertStringContainsString('previously imported: `12.1.0.69214`', $params['body']);

                return true;
            })
            ->andReturn(['number' => 4321]);

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenCommittedStateWithoutThisGameVersion_fallsBackToTheStateTableAndFilesAReminderIssue(): void
    {
        // Arrange
        $this->recordImportState('12.1.0.69214');
        $this->mockLatestBuild('12.1.0.69300');

        GitHub::shouldReceive('repo->contents->download')
            ->once()
            ->andReturn(json_encode([
                '999' => ['product' => 'wow_classic', 'build' => '12.1.0.69300', 'importedAt' => '2026-01-01 00:00:00'],
            ], JSON_THROW_ON_ERROR));

        GitHub::shouldReceive('issue->all')->once()->andReturn([]);
        GitHub::shouldReceive('issue->create')->once()->andReturn(['number' => 4321]);

        try {
            // Act & Assert
            $this->artisan('wagotools:checkforspelldescriptionpatch')->assertSuccessful();
        } finally {
            $this->clearImportState();
        }
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenUnknownGameVersion_failsWithoutCheckingForAPatch(): void
    {
        // Arrange
        $patchCheckService = $this->createMockPublic(SpellDescriptionPatchCheckServiceInterface::class);
        $patchCheckService->expects($this->never())->method('checkForPatch');
        app()->instance(SpellDescriptionPatchCheckServiceInterface::class, $patchCheckService);

        // Act & Assert
        $this->artisan('wagotools:checkforspelldescriptionpatch', ['--gameVersion' => 'not-a-game-version'])
            ->expectsOutputToContain('Unknown game version not-a-game-version')
            ->assertFailed();
    }

    /**
     * @throws Exception
     */
    private function mockLatestBuild(?string $build): void
    {
        $wagoToolsService = $this->createMockPublic(WagoToolsServiceInterface::class);
        $wagoToolsService->expects($this->once())->method('getLatestBuild')->with('wow')->willReturn($build);
        app()->instance(WagoToolsServiceInterface::class, $wagoToolsService);
    }

    /**
     * The committed database/data/spell_description/import_state.json as the GitHub contents API hands it
     * back - $build null for a file that records nothing for the game version under test.
     *
     * @throws \JsonException
     */
    private function mockCommittedImportState(?string $build): void
    {
        $state = $build === null ? [] : [
            (string)self::GAME_VERSION_ID => ['product' => 'wow', 'build' => $build, 'importedAt' => '2026-01-01 00:00:00'],
        ];

        GitHub::shouldReceive('repo->contents->download')
            ->once()
            ->withArgs(function (string $owner, string $repository, string $path): bool {
                $this->assertSame('RaiderIO', $owner);
                $this->assertSame('Keystone.guru', $repository);
                $this->assertSame('database/data/spell_description/import_state.json', $path);

                return true;
            })
            ->andReturn(json_encode($state, JSON_THROW_ON_ERROR));
    }

    private function recordImportState(string $build): void
    {
        SpellDescriptionImportState::query()->updateOrCreate(
            ['game_version_id' => self::GAME_VERSION_ID],
            ['product' => 'wow', 'build' => $build, 'imported_at' => now()],
        );
    }

    private function clearImportState(): void
    {
        SpellDescriptionImportState::query()->where('game_version_id', self::GAME_VERSION_ID)->delete();
    }
}
