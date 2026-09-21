<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeonInSeveralSeasons;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteContinueInNewerSeasonTest extends AjaxPublicTestCase
{
    use ProvidesDungeonInSeveralSeasons;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    #[Test]
    public function continueInNewerSeason_givenOwnRouteInOlderSeason_returnsCreatedCopyInNewestSeason(): void
    {
        // Arrange
        [$dungeon, $olderSeason, $newestSeason] = $this->findDungeonInSeveralSeasons();
        $source                                 = $this->createDungeonRouteInSeason($dungeon, $olderSeason);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/continue', $source->public_key));

            // Assert
            $response->assertCreated();

            /** @var DungeonRoute $continuation */
            $continuation = DungeonRoute::query()->where('public_key', $response->json('public_key'))->firstOrFail();
            $this->assertSame($source->public_key, $continuation->clone_of);
            $this->assertSame($newestSeason->id, $continuation->season_id);
        } finally {
            DungeonRoute::query()->where('clone_of', $source->public_key)->get()->each->delete();
            $source->delete();
        }
    }

    #[Test]
    public function continueInNewerSeason_givenRouteInNewestSeason_returnsUnprocessableEntity(): void
    {
        // Arrange
        [$dungeon, , $newestSeason] = $this->findDungeonInSeveralSeasons();
        $source                     = $this->createDungeonRouteInSeason($dungeon, $newestSeason);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/continue', $source->public_key));

            // Assert
            $response->assertUnprocessable();
            $this->assertSame(0, DungeonRoute::query()->where('clone_of', $source->public_key)->count());
        } finally {
            $source->delete();
        }
    }

    #[Test]
    public function continueInNewerSeason_givenGuest_returnsForbidden(): void
    {
        // Arrange
        [$dungeon, $olderSeason] = $this->findDungeonInSeveralSeasons();
        $source                  = $this->createDungeonRouteInSeason($dungeon, $olderSeason);
        Auth::logout();

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/continue', $source->public_key));

            // Assert
            $response->assertForbidden();
            $this->assertSame(0, DungeonRoute::query()->where('clone_of', $source->public_key)->count());
        } finally {
            $source->delete();
        }
    }

    #[Test]
    public function continueInNewerSeason_givenAnotherUsersUnpublishedRoute_returnsForbidden(): void
    {
        // Arrange
        [$dungeon, $olderSeason] = $this->findDungeonInSeveralSeasons();
        $source                  = $this->createDungeonRouteInSeason($dungeon, $olderSeason, [
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
            'expires_at'         => null,
        ]);
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);
        $this->actingAs($user);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/continue', $source->public_key));

            // Assert
            $response->assertForbidden();
            $this->assertSame(0, DungeonRoute::query()->where('clone_of', $source->public_key)->count());
        } finally {
            $source->delete();
            $user->delete();
        }
    }

    #[Test]
    public function get_givenOwnRouteInOlderSeason_stampsTheContinuationSeason(): void
    {
        // Arrange
        [$dungeon, $olderSeason, $newestSeason] = $this->findDungeonInSeveralSeasons();
        $title                                  = sprintf('Continuation stamp %s', uniqid());
        $source                                 = $this->createDungeonRouteInSeason($dungeon, $olderSeason, [
            'title'      => $title,
            'expires_at' => null,
        ]);

        try {
            // Act
            $response = $this->get($this->listQuery($title, ['mine' => 1]));

            // Assert
            $response->assertOk();
            $this->assertSame([$source->public_key], array_column($response->json('data'), 'public_key'));
            $this->assertSame($newestSeason->id, $response->json('data.0.continuation_season.id'));
            $this->assertSame($newestSeason->name_long, $response->json('data.0.continuation_season.name'));
        } finally {
            $source->delete();
        }
    }

    #[Test]
    public function get_givenOwnRouteInNewestSeason_stampsNoContinuationSeason(): void
    {
        // Arrange
        [$dungeon, , $newestSeason] = $this->findDungeonInSeveralSeasons();
        $title                      = sprintf('Continuation stamp %s', uniqid());
        $source                     = $this->createDungeonRouteInSeason($dungeon, $newestSeason, [
            'title'      => $title,
            'expires_at' => null,
        ]);

        try {
            // Act
            $response = $this->get($this->listQuery($title, ['mine' => 1]));

            // Assert
            $response->assertOk();
            $this->assertSame([$source->public_key], array_column($response->json('data'), 'public_key'));
            $this->assertNull($response->json('data.0.continuation_season'));
        } finally {
            $source->delete();
        }
    }

    #[Test]
    public function get_givenPublicList_doesNotStampTheContinuationSeason(): void
    {
        // Arrange
        [$dungeon, $olderSeason] = $this->findDungeonInSeveralSeasons();
        $title                   = sprintf('Continuation stamp %s', uniqid());
        $source                  = $this->createDungeonRouteInSeason($dungeon, $olderSeason, [
            'title'      => $title,
            'expires_at' => null,
        ]);

        try {
            // Act
            $response = $this->get($this->listQuery($title));

            // Assert
            $response->assertOk();
            $this->assertSame([$source->public_key], array_column($response->json('data'), 'public_key'));
            $this->assertArrayNotHasKey('continuation_season', $response->json('data.0'));
        } finally {
            $source->delete();
        }
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function listQuery(string $title, array $parameters = []): string
    {
        return sprintf('/ajax/routes?%s', http_build_query(array_merge([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'columns' => [
                [
                    'data'       => 'title',
                    'name'       => 'title',
                    'searchable' => 'true',
                    'orderable'  => 'true',
                    'search'     => ['value' => $title, 'regex' => 'false'],
                ],
            ],
            'order'  => [['column' => 0, 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ], $parameters)));
    }
}
