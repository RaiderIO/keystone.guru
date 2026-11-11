<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteAuthorizeBeforeRedirectTest extends PublicTestCase
{
    use ProvidesDungeon;

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('guestRedirectingRouteProvider')]
    public function redirectingRoute_givenUnpublishedRouteAndGuest_returnsForbiddenWithoutRedirect(
        string $routeName,
        string $method,
        array  $parameters,
    ): void {
        // Arrange
        $author = $this->createUser();
        $route  = $this->createUnpublishedRoute($author);

        try {
            // Act
            $response = $this->call($method, route($routeName, $this->routeParameters($routeName, $route) + $parameters));

            // Assert
            $this->assertForbiddenWithoutDisclosure($response, $route);
        } finally {
            $route->delete();
            $author->delete();
        }
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('redirectingRouteProvider')]
    public function redirectingRoute_givenUnpublishedRouteAndOtherUser_returnsForbiddenWithoutRedirect(
        string $routeName,
        string $method,
        array  $parameters,
    ): void {
        // Arrange
        $author    = $this->createUser();
        $otherUser = $this->createUser();
        $route     = $this->createUnpublishedRoute($author);

        try {
            // Act
            $response = $this->actingAs($otherUser)
                ->call($method, route($routeName, $this->routeParameters($routeName, $route) + $parameters));

            // Assert
            $this->assertForbiddenWithoutDisclosure($response, $route);
        } finally {
            $route->delete();
            $otherUser->delete();
            $author->delete();
        }
    }

    /**
     * @param array<string, string> $parameters
     */
    #[Test]
    #[DataProvider('redirectingRouteProvider')]
    public function redirectingRoute_givenUnpublishedRouteAndAuthor_redirectsToTheCanonicalUrl(
        string $routeName,
        string $method,
        array  $parameters,
    ): void {
        // Arrange
        $author = $this->createUser();
        $route  = $this->createUnpublishedRoute($author);

        try {
            // Act
            $response = $this->actingAs($author)
                ->call($method, route($routeName, $this->routeParameters($routeName, $route) + $parameters));

            // Assert
            $response->assertRedirect();
            $this->assertStringContainsString($route->getTitleSlug(), (string)$response->headers->get('Location'));
        } finally {
            $route->delete();
            $author->delete();
        }
    }

    /**
     * @return array<string, array{string, string, array<string, string>}>
     */
    public static function redirectingRouteProvider(): array
    {
        return self::guestRedirectingRouteProvider() + [
            // Behind the auth middleware, so a guest never reaches the gate
            'legacy clone' => ['dungeonroute.cloneold', 'POST', []],
        ];
    }

    /**
     * @return array<string, array{string, string, array<string, string>}>
     */
    public static function guestRedirectingRouteProvider(): array
    {
        return [
            'view no title'    => ['dungeonroute.viewnotitle', 'GET', []],
            'view'             => ['dungeonroute.view', 'GET', ['title' => 'outdated-title']],
            'present'          => ['dungeonroute.present', 'GET', ['title' => 'outdated-title']],
            'edit'             => ['dungeonroute.edit', 'GET', ['title' => 'outdated-title']],
            'legacy view'      => ['dungeonroute.viewold', 'GET', []],
            'legacy view flr'  => ['dungeonroute.viewold.floor', 'GET', ['floorIndex' => '1']],
            'legacy embed'     => ['dungeonroute.embedold', 'GET', []],
            'legacy embed flr' => ['dungeonroute.embedold.floor', 'GET', ['floorIndex' => '1']],
            'legacy edit'      => ['dungeonroute.editold', 'GET', []],
            'legacy edit flr'  => ['dungeonroute.editold.floor', 'GET', ['floorIndex' => '1']],
        ];
    }

    #[Test]
    public function previewOld_givenUnpublishedRouteAndAuthorWithoutSecret_returnsForbiddenWithoutRedirect(): void
    {
        // Arrange
        $author = $this->createUser();
        $route  = $this->createUnpublishedRoute($author);

        try {
            // Act
            $response = $this->actingAs($author)
                ->get(route('dungeonroute.previewold', ['dungeonRoute' => $route, 'floorIndex' => '1']));

            // Assert
            $this->assertForbiddenWithoutDisclosure($response, $route);
        } finally {
            $route->delete();
            $author->delete();
        }
    }

    #[Test]
    public function previewOld_givenUnpublishedRouteAndAdmin_redirectsToTheCanonicalUrl(): void
    {
        // Arrange
        $author = $this->createUser();
        $route  = $this->createUnpublishedRoute($author);

        try {
            // Act
            $response = $this->actingAs(User::findOrFail(1))
                ->get(route('dungeonroute.previewold', ['dungeonRoute' => $route, 'floorIndex' => '1']));

            // Assert
            $response->assertRedirect();
            $this->assertStringContainsString($route->getTitleSlug(), (string)$response->headers->get('Location'));
        } finally {
            $route->delete();
            $author->delete();
        }
    }

    #[Test]
    public function claimOld_givenUnpublishedRouteAndOtherUser_returnsForbiddenWithoutRedirect(): void
    {
        // Arrange
        $author    = $this->createUser();
        $otherUser = $this->createUser();
        $route     = $this->createUnpublishedRoute($author);

        try {
            // Act
            $response = $this->actingAs($otherUser)->post(route('dungeonroute.claimold', ['dungeonRoute' => $route]));

            // Assert
            $this->assertForbiddenWithoutDisclosure($response, $route);
        } finally {
            $route->delete();
            $otherUser->delete();
            $author->delete();
        }
    }

    #[Test]
    public function claimOld_givenSandboxRouteAndOtherUser_redirectsToTheCanonicalUrl(): void
    {
        // Arrange
        $author    = $this->createUser();
        $otherUser = $this->createUser();
        $route     = $this->createUnpublishedRoute($author);
        $route->update(['expires_at' => now()->addHour()]);

        try {
            // Act
            $response = $this->actingAs($otherUser)->post(route('dungeonroute.claimold', ['dungeonRoute' => $route]));

            // Assert
            $response->assertRedirect(route('dungeonroute.claim', [
                'dungeon'      => $route->dungeon,
                'dungeonroute' => $route,
                'title'        => $route->getTitleSlug(),
            ]));
        } finally {
            $route->delete();
            $otherUser->delete();
            $author->delete();
        }
    }

    #[Test]
    public function embed_givenUnpublishedRouteAndGuest_returnsForbidden(): void
    {
        // Arrange
        $author = $this->createUser();
        $route  = $this->createUnpublishedRoute($author);

        try {
            // Act
            $response = $this->get(route('misc.embed', ['dungeonRoute' => $route]));

            // Assert
            $this->assertForbiddenWithoutDisclosure($response, $route);
        } finally {
            $route->delete();
            $author->delete();
        }
    }

    #[Test]
    public function embed_givenWorldRouteAndGuest_returnsOk(): void
    {
        // Arrange
        $author = $this->createUser();
        $route  = $this->createUnpublishedRoute($author);
        $route->update(['published_state_id' => PublishedState::ALL[PublishedState::WORLD]]);

        try {
            // Act
            $response = $this->get(route('misc.embed', ['dungeonRoute' => $route]));

            // Assert
            $response->assertOk();
            $response->assertSee($route->getTitleSlug(), false);
        } finally {
            $route->delete();
            $author->delete();
        }
    }

    /**
     * @param TestResponse<Response> $response
     */
    private function assertForbiddenWithoutDisclosure(TestResponse $response, DungeonRoute $route): void
    {
        $response->assertForbidden();
        $this->assertStringNotContainsString($route->getTitleSlug(), (string)$response->headers->get('Location'));
        $this->assertStringNotContainsString($route->getTitleSlug(), (string)$response->getContent());
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParameters(string $routeName, DungeonRoute $route): array
    {
        if (str_contains($routeName, 'old')) {
            return ['dungeonRoute' => $route];
        }

        return [
            'dungeon'      => $route->dungeon,
            'dungeonroute' => $route,
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

    private function createUnpublishedRoute(User $author): DungeonRoute
    {
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 1, requireDefaultFloor: true);

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'author_id'          => $author->id,
            'title'              => sprintf('Private plan %s', Str::random(12)),
            'expires_at'         => null,
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);
    }
}
