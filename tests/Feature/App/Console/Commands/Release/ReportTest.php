<?php

namespace Tests\Feature\App\Console\Commands\Release;

use App\Repositories\Interfaces\ReleaseReportLogRepositoryInterface;
use App\Service\Discord\DiscordApiServiceInterface;
use GrahamCampbell\GitHub\Facades\GitHub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Fixtures\RepositoryFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('Release')]
final class ReportTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenReleaseWithPublicChanges_sendsDiscordEmbedsAndLogsReport(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease('v15.3.3', '* #1234 Fixed a bug.'),
            $this->githubRelease('v15.3.2', '* #1230 Some other change.'),
        ]);

        $discordApiService = $this->createMockPublic(DiscordApiServiceInterface::class);
        $discordApiService->expects($this->once())->method('sendEmbeds')
            ->willReturnCallback(function (string $webhookUrl, array $embeds): bool {
                $this->assertStringContainsString('* #1234 Fixed a bug.', $embeds[0]['description']);
                // Same major version - no @everyone mention
                $this->assertStringNotContainsString('@everyone', $embeds[0]['description']);
                $this->assertSame('https://github.com/test/test/releases/tag/v15.3.3', $embeds[0]['url']);

                return true;
            });
        app()->instance(DiscordApiServiceInterface::class, $discordApiService);

        $releaseReportLogRepository = RepositoryFixtures::getReleaseReportLogRepositoryMock($this);
        $releaseReportLogRepository->expects($this->once())->method('create')
            ->with(['version' => 'v15.3.3', 'platform' => 'discord']);
        app()->instance(ReleaseReportLogRepositoryInterface::class, $releaseReportLogRepository);

        // Act & Assert
        $this->artisan('release:report', ['version' => 'v15.3.3'])->assertExitCode(0);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenMajorUpgrade_mentionsEveryone(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease('v16.0.0', '* #1234 Big new feature.'),
            $this->githubRelease('v15.3.3', '* #1230 Some other change.'),
        ]);

        $discordApiService = $this->createMockPublic(DiscordApiServiceInterface::class);
        $discordApiService->expects($this->once())->method('sendEmbeds')
            ->willReturnCallback(function (string $webhookUrl, array $embeds): bool {
                $this->assertStringContainsString('@everyone', $embeds[0]['description']);

                return true;
            });
        app()->instance(DiscordApiServiceInterface::class, $discordApiService);

        $releaseReportLogRepository = RepositoryFixtures::getReleaseReportLogRepositoryMock($this);
        app()->instance(ReleaseReportLogRepositoryInterface::class, $releaseReportLogRepository);

        // Act & Assert
        $this->artisan('release:report')->assertExitCode(0);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenEmptyReleaseBody_doesNotReport(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease('v15.3.3', ''),
        ]);

        $discordApiService = $this->createMockPublic(DiscordApiServiceInterface::class);
        $discordApiService->expects($this->never())->method('sendEmbeds');
        app()->instance(DiscordApiServiceInterface::class, $discordApiService);

        $releaseReportLogRepository = RepositoryFixtures::getReleaseReportLogRepositoryMock($this);
        $releaseReportLogRepository->expects($this->never())->method('create');
        app()->instance(ReleaseReportLogRepositoryInterface::class, $releaseReportLogRepository);

        // Act & Assert
        $this->artisan('release:report', ['version' => 'v15.3.3'])->assertExitCode(0);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenAlreadyReportedRelease_doesNotReportAgain(): void
    {
        // Arrange
        config()->set('app.type', 'production');

        $this->mockGithubReleases([
            $this->githubRelease('v15.3.3', '* #1234 Fixed a bug.'),
        ]);

        $discordApiService = $this->createMockPublic(DiscordApiServiceInterface::class);
        $discordApiService->expects($this->never())->method('sendEmbeds');
        app()->instance(DiscordApiServiceInterface::class, $discordApiService);

        $releaseReportLogRepository = RepositoryFixtures::getReleaseReportLogRepositoryMock($this);
        $releaseReportLogRepository->expects($this->once())->method('hasReportedVersionOnPlatform')
            ->with('v15.3.3', 'discord')
            ->willReturn(true);
        $releaseReportLogRepository->expects($this->never())->method('create');
        app()->instance(ReleaseReportLogRepositoryInterface::class, $releaseReportLogRepository);

        // Act & Assert
        $this->artisan('release:report', ['version' => 'v15.3.3'])->assertExitCode(0);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenUnknownVersion_returnsFailure(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease('v15.3.3', '* #1234 Fixed a bug.'),
        ]);

        $discordApiService = $this->createMockPublic(DiscordApiServiceInterface::class);
        $discordApiService->expects($this->never())->method('sendEmbeds');
        app()->instance(DiscordApiServiceInterface::class, $discordApiService);

        $releaseReportLogRepository = RepositoryFixtures::getReleaseReportLogRepositoryMock($this);
        app()->instance(ReleaseReportLogRepositoryInterface::class, $releaseReportLogRepository);

        // Act & Assert
        $this->artisan('release:report', ['version' => 'v99.99.99'])->assertExitCode(1);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenDraftAndPrerelease_ignoresThemWhenResolvingLatest(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease('v15.3.5', '* #1240 Not rolled out yet.', draft: true),
            $this->githubRelease('v15.3.4', '* #1236 Testing the waters.', prerelease: true),
            $this->githubRelease('v15.3.3', '* #1234 Fixed a bug.'),
        ]);

        $discordApiService = $this->createMockPublic(DiscordApiServiceInterface::class);
        $discordApiService->expects($this->once())->method('sendEmbeds')
            ->willReturnCallback(function (string $webhookUrl, array $embeds): bool {
                $this->assertStringContainsString('v15.3.3', $embeds[0]['title']);

                return true;
            });
        app()->instance(DiscordApiServiceInterface::class, $discordApiService);

        $releaseReportLogRepository = RepositoryFixtures::getReleaseReportLogRepositoryMock($this);
        app()->instance(ReleaseReportLogRepositoryInterface::class, $releaseReportLogRepository);

        // Act & Assert
        $this->artisan('release:report')->assertExitCode(0);
    }

    /** @param array<int, array<string, mixed>> $githubReleases */
    private function mockGithubReleases(array $githubReleases): void
    {
        GitHub::shouldReceive('repo->releases->all')->andReturn($githubReleases);
    }

    /** @return array<string, mixed> */
    private function githubRelease(string $version, string $body, bool $draft = false, bool $prerelease = false): array
    {
        return [
            'tag_name'     => $version,
            'name'         => $version,
            'body'         => $body,
            'draft'        => $draft,
            'prerelease'   => $prerelease,
            'published_at' => '2026-07-09T12:00:00Z',
            'html_url'     => sprintf('https://github.com/test/test/releases/tag/%s', $version),
        ];
    }
}
