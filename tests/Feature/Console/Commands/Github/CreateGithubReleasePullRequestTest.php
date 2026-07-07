<?php

namespace Tests\Feature\Console\Commands\Github;

use App\Console\Commands\Github\CreateGithubReleasePullRequest;
use App\Models\Release;
use App\Models\ReleaseChangelog;
use Github\Api\Issue;
use Github\Api\PullRequest;
use GrahamCampbell\GitHub\Facades\GitHub;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Github')]
final class CreateGithubReleasePullRequestTest extends PublicTestCase
{
    private const string VERSION_ARGUMENT = '9999.9.9-test';

    private const string VERSION = 'v9999.9.9-test';

    private ?int $releaseChangelogId = null;

    protected function tearDown(): void
    {
        try {
            Release::where('version', self::VERSION)->delete();

            if ($this->releaseChangelogId !== null) {
                ReleaseChangelog::where('id', $this->releaseChangelogId)->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function createTestRelease(): Release
    {
        $changelog = ReleaseChangelog::create([
            'release_id'  => 0,
            'description' => '',
        ]);
        $this->releaseChangelogId = $changelog->id;

        $release = Release::create([
            'release_changelog_id' => $changelog->id,
            'version'              => self::VERSION,
            'title'                => 'Test Release',
            'backup_db'            => false,
            'silent'               => true,
            'spotlight'            => false,
            'released'             => false,
        ]);

        // Release::changelog() is a hasOne keyed by release_id on the changelog row, so it
        // must point back at the release before it can be eager-loaded.
        $changelog->update(['release_id' => $release->id]);

        return $release;
    }

    #[Test]
    public function handle_givenExistingPullRequestForSameRepo_updatesInsteadOfCreating(): void
    {
        // Arrange
        $this->createTestRelease();

        $existingPullRequestNumber = 4242;

        /** @var Mockery\MockInterface&PullRequest $pullRequestClient */
        $pullRequestClient = Mockery::mock(PullRequest::class);
        $pullRequestClient->shouldReceive('all')
            // @phpstan-ignore method.notFound
            ->once()
            ->with('RaiderIO', 'Keystone.guru', ['state' => 'open', 'labels' => 'release'])
            // The GitHub API normalizes the repo full_name's case, which used to break the
            // string-based match in the command - see issue #3368.
            ->andReturn([
                [
                    'number' => $existingPullRequestNumber,
                    'head'   => [
                        'ref'  => 'development',
                        'repo' => ['full_name' => 'RaiderIO/keystone.guru'],
                    ],
                    'base' => [
                        'ref'  => 'master',
                        'repo' => ['full_name' => 'RaiderIO/keystone.guru'],
                    ],
                ],
            ]);
        $pullRequestClient->shouldReceive('update')
            // @phpstan-ignore method.notFound
            ->once()
            ->with('RaiderIO', 'Keystone.guru', $existingPullRequestNumber, Mockery::type('array'))
            ->andReturn([]);
        $pullRequestClient->shouldNotReceive('create');

        GitHub::shouldReceive('pr')->andReturn($pullRequestClient);

        // Act
        $this->artisan(CreateGithubReleasePullRequest::class, ['version' => self::VERSION_ARGUMENT])
            ->assertSuccessful();
    }

    #[Test]
    public function handle_givenNoMatchingPullRequestExists_createsNewPullRequest(): void
    {
        // Arrange
        $this->createTestRelease();

        /** @var Mockery\MockInterface&PullRequest $pullRequestClient */
        $pullRequestClient = Mockery::mock(PullRequest::class);
        $pullRequestClient->shouldReceive('all')
            // @phpstan-ignore method.notFound
            ->once()
            ->with('RaiderIO', 'Keystone.guru', ['state' => 'open', 'labels' => 'release'])
            ->andReturn([]);
        $pullRequestClient->shouldReceive('create')
            // @phpstan-ignore method.notFound
            ->once()
            ->with('RaiderIO', 'Keystone.guru', Mockery::type('array'))
            ->andReturn(['id' => 555, 'number' => 42]);
        $pullRequestClient->shouldNotReceive('update');

        /** @var Mockery\MockInterface&Issue $issueClient */
        $issueClient = Mockery::mock(Issue::class);
        $issueClient->shouldReceive('update')
            // @phpstan-ignore method.notFound
            ->once()
            ->with('RaiderIO', 'Keystone.guru', 42, Mockery::type('array'));

        GitHub::shouldReceive('pr')->andReturn($pullRequestClient);
        GitHub::shouldReceive('issues')->andReturn($issueClient);

        // Act
        $this->artisan(CreateGithubReleasePullRequest::class, ['version' => self::VERSION_ARGUMENT])
            ->assertSuccessful();
    }
}
