<?php

namespace Tests\Feature\Controller;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\LiveSession;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('LiveSession')]
final class LiveSessionLegacyControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('legacyLiveSessionRouteProvider')]
    public function legacyLiveSession_givenUnpublishedRouteAndOtherUser_returnsForbiddenWithoutRedirect(
        string $routeName,
        array  $parameters,
    ): void {
        // Arrange
        $author      = $this->createUser();
        $otherUser   = $this->createUser();
        $route       = $this->createRoute($author, PublishedState::UNPUBLISHED);
        $liveSession = $this->createLiveSession($route, $author);

        try {
            // Act
            $response = $this->actingAs($otherUser)->get(route($routeName, [
                'dungeonRoute' => $route,
                'liveSession'  => $liveSession,
            ] + $parameters));

            // Assert
            $response->assertForbidden();
            $this->assertStringNotContainsString($route->getTitleSlug(), (string)$response->headers->get('Location'));
            $this->assertStringNotContainsString($route->getTitleSlug(), (string)$response->getContent());
        } finally {
            LiveSession::query()->whereKey($liveSession->id)->delete();
            $route->delete();
            $otherUser->delete();
            $author->delete();
        }
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('legacyLiveSessionRouteProvider')]
    public function legacyLiveSession_givenLiveSessionOfAnotherRoute_returnsNotFound(
        string $routeName,
        array  $parameters,
    ): void {
        // Arrange
        $author      = $this->createUser();
        $route       = $this->createRoute($author, PublishedState::WORLD);
        $otherRoute  = $this->createRoute($author, PublishedState::WORLD);
        $liveSession = $this->createLiveSession($otherRoute, $author);

        try {
            // Act
            $response = $this->actingAs($author)->get(route($routeName, [
                'dungeonRoute' => $route,
                'liveSession'  => $liveSession,
            ] + $parameters));

            // Assert
            $response->assertNotFound();
            $this->assertNull($response->headers->get('Location'));
        } finally {
            LiveSession::query()->whereKey($liveSession->id)->delete();
            $otherRoute->delete();
            $route->delete();
            $author->delete();
        }
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('legacyLiveSessionRouteProvider')]
    public function legacyLiveSession_givenUnpublishedRouteAndAuthor_redirectsToTheLiveSession(
        string $routeName,
        array  $parameters,
    ): void {
        // Arrange
        $author      = $this->createUser();
        $route       = $this->createRoute($author, PublishedState::UNPUBLISHED);
        $liveSession = $this->createLiveSession($route, $author);

        try {
            // Act
            $response = $this->actingAs($author)->get(route($routeName, [
                'dungeonRoute' => $route,
                'liveSession'  => $liveSession,
            ] + $parameters));

            // Assert
            $response->assertRedirect(route('dungeonroute.livesession.view', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
                'liveSession'  => $liveSession,
            ] + $parameters));
        } finally {
            LiveSession::query()->whereKey($liveSession->id)->delete();
            $route->delete();
            $author->delete();
        }
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function legacyLiveSessionRouteProvider(): array
    {
        return [
            'view'       => ['dungeonroute.livesession.viewold', []],
            'view floor' => ['dungeonroute.livesession.viewfloorold', ['floorIndex' => '1']],
        ];
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);
        $user->legal_agreed = true;
        $user->save();

        return $user;
    }

    private function createRoute(User $author, string $publishedState): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => $author->id,
            'title'              => sprintf('Private plan %s', Str::random(12)),
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[$publishedState],
        ]);
    }

    private function createLiveSession(DungeonRoute $route, User $user): LiveSession
    {
        return LiveSession::create([
            'dungeon_route_id' => $route->id,
            'user_id'          => $user->id,
            'public_key'       => LiveSession::generateRandomPublicKey(),
        ]);
    }
}
