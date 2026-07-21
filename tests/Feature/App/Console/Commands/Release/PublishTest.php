<?php

namespace Tests\Feature\App\Console\Commands\Release;

use GrahamCampbell\GitHub\Facades\GitHub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Release')]
final class PublishTest extends PublicTestCase
{
    #[Test]
    public function handle_givenDraftRelease_publishesIt(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease(101, 'v15.6.0', draft: true),
        ]);

        GitHub::shouldReceive('repo->releases->edit')
            ->once()
            ->with('RaiderIO', 'Keystone.guru', 101, ['draft' => false])
            ->andReturn([]);

        // Act & Assert
        $this->artisan('release:publish', ['tag' => 'v15.6.0'])->assertExitCode(0);
    }

    #[Test]
    public function handle_givenAlreadyPublishedRelease_doesNotEditIt(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease(101, 'v15.6.0', draft: false),
        ]);

        GitHub::shouldReceive('repo->releases->edit')->never();

        // Act & Assert
        $this->artisan('release:publish', ['tag' => 'v15.6.0'])->assertExitCode(0);
    }

    #[Test]
    public function handle_givenUnknownTag_returnsFailure(): void
    {
        // Arrange
        $this->mockGithubReleases([
            $this->githubRelease(101, 'v15.6.0', draft: true),
        ]);

        GitHub::shouldReceive('repo->releases->edit')->never();

        // Act & Assert
        $this->artisan('release:publish', ['tag' => 'v99.99.99'])->assertExitCode(1);
    }

    /** @param array<int, array<string, mixed>> $githubReleases */
    private function mockGithubReleases(array $githubReleases): void
    {
        GitHub::shouldReceive('repo->releases->all')->andReturn($githubReleases);
    }

    /** @return array<string, mixed> */
    private function githubRelease(int $id, string $tag, bool $draft): array
    {
        return [
            'id'       => $id,
            'tag_name' => $tag,
            'name'     => $tag,
            'draft'    => $draft,
        ];
    }
}
