<?php

namespace Tests\Feature\Controller\Ajax;

use App\Http\Requests\DungeonRoute\AjaxDungeonRouteDeleteBulkFormRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteChange;
use App\Models\DungeonRoute\DungeonRouteFavorite;
use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The endpoint behind the route picker drawer's delete mode: it deletes several of the caller's own routes at
 * once, each through Eloquent so every route's deleting hook still cleans up after it.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class AjaxDungeonRouteControllerDeleteBulkTest extends PublicTestCase
{
    /** @var array<int, User> */
    private array $createdUsers = [];

    /** @var array<int, DungeonRoute> */
    private array $createdDungeonRoutes = [];

    /** @var array<int, Team> */
    private array $createdTeams = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function (): void {
            foreach ($this->createdDungeonRoutes as $dungeonRoute) {
                $dungeonRoute->fresh()?->delete();
            }

            foreach ($this->createdTeams as $team) {
                $team->delete();
            }

            foreach ($this->createdUsers as $user) {
                $user->delete();
            }
        });
    }

    #[Test]
    public function deleteBulk_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $author       = $this->createUser();
        $dungeonRoute = $this->createRoute($author);

        // Act
        $response = $this->deleteBulk(null, [$dungeonRoute->public_key]);

        // Assert
        $response->assertUnauthorized();
        $this->assertNotNull($dungeonRoute->fresh());
    }

    #[Test]
    public function deleteBulk_givenOwnRoutes_deletesThemAndReturnsTheirPublicKeys(): void
    {
        // Arrange
        $author      = $this->createUser();
        $firstRoute  = $this->createRoute($author);
        $secondRoute = $this->createRoute($author);
        $keptRoute   = $this->createRoute($author);

        // Act
        $response = $this->deleteBulk($author, [$firstRoute->public_key, $secondRoute->public_key]);

        // Assert
        $response->assertOk();
        $response->assertJsonCount(2, 'dungeon_routes');
        $this->assertEqualsCanonicalizing(
            [$firstRoute->public_key, $secondRoute->public_key],
            $response->json('dungeon_routes'),
        );
        $this->assertNull($firstRoute->fresh());
        $this->assertNull($secondRoute->fresh());
        $this->assertNotNull($keptRoute->fresh());
    }

    #[Test]
    public function deleteBulk_givenOwnRoutes_runsEachRoutesDeletingHook(): void
    {
        // Arrange
        $author       = $this->createUser();
        $dungeonRoute = $this->createRoute($author);
        $favorite     = DungeonRouteFavorite::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'user_id'          => $author->id,
        ]);

        // Act
        $response = $this->deleteBulk($author, [$dungeonRoute->public_key]);

        // Assert
        $response->assertOk();
        $this->assertNull(DungeonRouteFavorite::query()->find($favorite->id));
    }

    #[Test]
    public function deleteBulk_givenAMixOfOwnAndForeignRoutes_returnsForbiddenAndDeletesNothing(): void
    {
        // Arrange
        $author       = $this->createUser();
        $otherAuthor  = $this->createUser();
        $ownRoute     = $this->createRoute($author);
        $foreignRoute = $this->createRoute($otherAuthor);

        // Act
        $response = $this->deleteBulk($author, [$ownRoute->public_key, $foreignRoute->public_key]);

        // Assert
        $response->assertForbidden();
        $this->assertNotNull($ownRoute->fresh());
        $this->assertNotNull($foreignRoute->fresh());
    }

    #[Test]
    public function deleteBulk_givenAnAdmin_deletesRoutesOfOtherAuthors(): void
    {
        // Arrange
        $author       = $this->createUser();
        $admin        = User::findOrFail(1);
        $dungeonRoute = $this->createRoute($author);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');

        // Act
        $response = $this->deleteBulk($admin, [$dungeonRoute->public_key]);

        // Assert
        $response->assertOk();
        $this->assertNull($dungeonRoute->fresh());
    }

    #[Test]
    public function deleteBulk_givenATeamRoute_writesTheTeamsChangeLog(): void
    {
        // Arrange
        $author       = $this->createUser();
        $team         = $this->createTeam($author);
        $dungeonRoute = $this->createRoute($author, $team->id);

        // Act
        $response = $this->deleteBulk($author, [$dungeonRoute->public_key]);

        // Assert
        $response->assertOk();
        $this->assertNull($dungeonRoute->fresh());
        $this->assertTrue(DungeonRouteChange::query()
            ->where('dungeon_route_id', $dungeonRoute->id)
            ->where('team_id', $team->id)
            ->exists());
    }

    #[Test]
    public function deleteBulk_givenAnEmptyArray_returnsValidationError(): void
    {
        // Arrange
        $author = $this->createUser();

        // Act
        $response = $this->deleteBulk($author, []);

        // Assert
        $response->assertJsonValidationErrors('dungeon_routes');
    }

    #[Test]
    public function deleteBulk_givenAnUnknownPublicKey_returnsValidationErrorAndDeletesNothing(): void
    {
        // Arrange
        $author       = $this->createUser();
        $dungeonRoute = $this->createRoute($author);

        // Act
        $response = $this->deleteBulk($author, [$dungeonRoute->public_key, 'nosuchkey']);

        // Assert
        $response->assertJsonValidationErrors('dungeon_routes.1');
        $this->assertNotNull($dungeonRoute->fresh());
    }

    #[Test]
    public function deleteBulk_givenDuplicatePublicKeys_returnsValidationErrorAndDeletesNothing(): void
    {
        // Arrange
        $author       = $this->createUser();
        $dungeonRoute = $this->createRoute($author);

        // Act
        $response = $this->deleteBulk($author, [$dungeonRoute->public_key, $dungeonRoute->public_key]);

        // Assert
        $response->assertJsonValidationErrors('dungeon_routes.0');
        $this->assertNotNull($dungeonRoute->fresh());
    }

    #[Test]
    public function deleteBulk_givenMoreRoutesThanTheLimit_returnsValidationError(): void
    {
        // Arrange
        $author     = $this->createUser();
        $publicKeys = [];
        for ($index = 0; $index <= AjaxDungeonRouteDeleteBulkFormRequest::MAX_DUNGEON_ROUTES; $index++) {
            $publicKeys[] = sprintf('key%d', $index);
        }

        // Act
        $response = $this->deleteBulk($author, $publicKeys);

        // Assert
        $response->assertJsonValidationErrors('dungeon_routes');
    }

    /**
     * @param  array<int, string>         $publicKeys
     * @return TestResponse<JsonResponse>
     */
    private function deleteBulk(?User $user, array $publicKeys): TestResponse
    {
        $request = $this->withHeader('X-Requested-With', 'XMLHttpRequest');
        if ($user !== null) {
            $request = $request->actingAs($user);
        }

        return $request->deleteJson(route('api.dungeonroute.delete.bulk'), ['dungeon_routes' => $publicKeys]);
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $user->addRole(Role::ROLE_USER);
        $this->createdUsers[] = $user;

        return $user;
    }

    private function createTeam(User $owner): Team
    {
        $team = Team::create([
            'public_key'  => sprintf('t%s', substr(md5(uniqid('', true)), 0, 6)),
            'name'        => 'Delete Bulk Raiders',
            'description' => 'Team of the mass delete test',
        ]);
        $team->addMember($owner, 'admin');
        $this->createdTeams[] = $team;

        return $team;
    }

    private function createRoute(User $author, int $teamId = -1): DungeonRoute
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()
            ->where('active', true)
            ->whereHas('floors')
            ->get()
            ->first(static fn(Dungeon $dungeon): bool => $dungeon->getCurrentMappingVersion() !== null);

        $dungeonRoute = DungeonRoute::factory()->create([
            'author_id'          => $author->id,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'team_id'            => $teamId,
            'expires_at'         => null,
        ]);
        $this->createdDungeonRoutes[] = $dungeonRoute;

        return $dungeonRoute;
    }
}
