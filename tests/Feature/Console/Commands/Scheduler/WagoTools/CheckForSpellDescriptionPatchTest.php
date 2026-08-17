<?php

namespace Tests\Feature\Console\Commands\Scheduler\WagoTools;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\SpellDescriptionImportState;
use App\Service\WagoTools\WagoToolsServiceInterface;
use GrahamCampbell\GitHub\Facades\GitHub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('SpellDescription')]
final class CheckForSpellDescriptionPatchTest extends PublicTestCase
{
    private const int GAME_VERSION_ID = GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL];

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
    private function mockLatestBuild(?string $build): void
    {
        $wagoToolsService = $this->createMockPublic(WagoToolsServiceInterface::class);
        $wagoToolsService->expects($this->once())->method('getLatestBuild')->with('wow')->willReturn($build);
        app()->instance(WagoToolsServiceInterface::class, $wagoToolsService);
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
