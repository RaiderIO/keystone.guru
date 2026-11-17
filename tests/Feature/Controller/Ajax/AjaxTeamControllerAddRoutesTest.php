<?php

namespace Tests\Feature\Controller\Ajax;

use App\Http\Requests\Team\TeamAddRoutesFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * POST /ajax/team/{team}/route: the route picker adds a list of routes to a team in one request, under
 * the same rules as adding a single route through /ajax/team/{team}/route/{dungeonroute}.
 */
#[Group('Controller')]
#[Group('Team')]
final class AjaxTeamControllerAddRoutesTest extends AjaxPublicTestCase
{
    private Team $team;

    private User $moderator;

    private User $member;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = $this->createTeam();

        $this->moderator = User::factory()->create();
        $this->moderator->addRole(Role::ROLE_USER);

        $this->member = User::factory()->create();
        $this->member->addRole(Role::ROLE_USER);

        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $this->moderator->id, 'role' => TeamUser::ROLE_MODERATOR]);
        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $this->member->id, 'role' => TeamUser::ROLE_MEMBER]);

        $this->actingAs($this->moderator);
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->team->delete();
            $this->member->delete();
            $this->moderator->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function addRoutes_givenTheCallersOwnRoutes_assignsThemAllAndReturnsTheirPublicKeys(): void
    {
        // Arrange
        $firstRoute  = $this->createRoute($this->moderator);
        $secondRoute = $this->createRoute($this->moderator);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), [
                'dungeon_routes' => [$firstRoute->public_key, $secondRoute->public_key],
            ]);

            // Assert
            $response->assertOk();
            $this->assertEqualsCanonicalizing(
                [$firstRoute->public_key, $secondRoute->public_key],
                $response->json('public_keys'),
            );
            $this->assertSame($this->team->id, $this->teamIdOf($firstRoute));
            $this->assertSame($this->team->id, $this->teamIdOf($secondRoute));
        } finally {
            $firstRoute->delete();
            $secondRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenACollaboratorsOwnRoutes_assignsThem(): void
    {
        // Arrange
        $collaborator = User::factory()->create();
        $collaborator->addRole(Role::ROLE_USER);
        TeamUser::create(['team_id' => $this->team->id, 'user_id' => $collaborator->id, 'role' => TeamUser::ROLE_COLLABORATOR]);
        $dungeonRoute = $this->createRoute($collaborator);
        $this->actingAs($collaborator);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), ['dungeon_routes' => [$dungeonRoute->public_key]]);

            // Assert
            $response->assertOk();
            $this->assertSame([$dungeonRoute->public_key], $response->json('public_keys'));
            $this->assertSame($this->team->id, $this->teamIdOf($dungeonRoute));
        } finally {
            $dungeonRoute->delete();
            $collaborator->delete();
        }
    }

    #[Test]
    public function addRoutes_givenASiteAdminModeratorAddingOtherMembersRoutes_returnsForbiddenAndAssignsNone(): void
    {
        // Arrange - the team role allows adding, so only the routes' authorship stands in the way
        $siteAdmin = User::factory()->create();
        $siteAdmin->addRole(Role::ROLE_ADMIN);
        $teamUser    = TeamUser::create(['team_id' => $this->team->id, 'user_id' => $siteAdmin->id, 'role' => TeamUser::ROLE_MODERATOR]);
        $firstRoute  = $this->createRoute($this->member);
        $secondRoute = $this->createRoute($this->moderator);
        $this->actingAs($siteAdmin);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), [
                'dungeon_routes' => [$firstRoute->public_key, $secondRoute->public_key],
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertNull($this->teamIdOf($firstRoute));
            $this->assertNull($this->teamIdOf($secondRoute));
        } finally {
            $firstRoute->delete();
            $secondRoute->delete();
            $teamUser->delete();
            $siteAdmin->delete();
        }
    }

    #[Test]
    public function addRoutes_givenOneRouteByAnotherTeamMember_returnsForbiddenAndAssignsNone(): void
    {
        // Arrange - the caller moderates the team, but only authored one of the two routes
        $ownRoute    = $this->createRoute($this->moderator);
        $memberRoute = $this->createRoute($this->member);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), [
                'dungeon_routes' => [$ownRoute->public_key, $memberRoute->public_key],
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertNull($this->teamIdOf($ownRoute));
            $this->assertNull($this->teamIdOf($memberRoute));
        } finally {
            $ownRoute->delete();
            $memberRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenAnUnpublishedOwnRoute_assignsIt(): void
    {
        // Arrange - adding a single route never looks at its published state, and neither does the list
        $dungeonRoute = $this->createRoute($this->moderator, [
            'published_state_id' => PublishedState::ALL[PublishedState::UNPUBLISHED],
        ]);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), ['dungeon_routes' => [$dungeonRoute->public_key]]);

            // Assert
            $response->assertOk();
            $this->assertSame([$dungeonRoute->public_key], $response->json('public_keys'));
            $this->assertSame($this->team->id, $this->teamIdOf($dungeonRoute));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenTheSameRouteTwice_assignsItOnce(): void
    {
        // Arrange
        $dungeonRoute = $this->createRoute($this->moderator);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), [
                'dungeon_routes' => [$dungeonRoute->public_key, $dungeonRoute->public_key],
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame([$dungeonRoute->public_key], $response->json('public_keys'));
            $this->assertSame($this->team->id, $this->teamIdOf($dungeonRoute));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenARouteAlreadyOnThisTeam_returnsOkWithoutItsPublicKey(): void
    {
        // Arrange - already on the team is not an error, but it was not added by this request either
        $alreadyOnTeam = $this->createRoute($this->moderator, ['team_id' => $this->team->id]);
        $newRoute      = $this->createRoute($this->moderator);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), [
                'dungeon_routes' => [$alreadyOnTeam->public_key, $newRoute->public_key],
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame([$newRoute->public_key], $response->json('public_keys'));
            $this->assertSame($this->team->id, $this->teamIdOf($alreadyOnTeam));
            $this->assertSame($this->team->id, $this->teamIdOf($newRoute));
        } finally {
            $alreadyOnTeam->delete();
            $newRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenOneRouteAuthoredOutsideTheTeam_returnsForbiddenAndAssignsNone(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);

        $ownRoute      = $this->createRoute($this->moderator);
        $outsiderRoute = $this->createRoute($outsider);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), [
                'dungeon_routes' => [$ownRoute->public_key, $outsiderRoute->public_key],
            ]);

            // Assert
            $response->assertForbidden();
            $this->assertNull($this->teamIdOf($ownRoute));
            $this->assertNull($this->teamIdOf($outsiderRoute));
        } finally {
            $ownRoute->delete();
            $outsiderRoute->delete();
            $outsider->delete();
        }
    }

    #[Test]
    public function addRoutes_givenOneRouteOnAnotherTeam_returnsNotFoundAndAssignsNone(): void
    {
        // Arrange
        $otherTeam       = $this->createTeam();
        $ownRoute        = $this->createRoute($this->moderator);
        $otherTeamsRoute = $this->createRoute($this->moderator, ['team_id' => $otherTeam->id]);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), [
                'dungeon_routes' => [$ownRoute->public_key, $otherTeamsRoute->public_key],
            ]);

            // Assert
            $response->assertNotFound();
            $this->assertNull($this->teamIdOf($ownRoute));
            $this->assertSame($otherTeam->id, $this->teamIdOf($otherTeamsRoute));
        } finally {
            $ownRoute->delete();
            $otherTeamsRoute->delete();
            $otherTeam->delete();
        }
    }

    #[Test]
    public function addRoutes_givenATeamMemberWithoutModeratorRole_returnsForbidden(): void
    {
        // Arrange
        $dungeonRoute = $this->createRoute($this->member);
        $this->actingAs($this->member);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), ['dungeon_routes' => [$dungeonRoute->public_key]]);

            // Assert
            $response->assertForbidden();
            $this->assertNull($this->teamIdOf($dungeonRoute));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenANonMember_returnsForbidden(): void
    {
        // Arrange
        $outsider = User::factory()->create();
        $outsider->addRole(Role::ROLE_USER);
        $dungeonRoute = $this->createRoute($this->member);
        $this->actingAs($outsider);

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), ['dungeon_routes' => [$dungeonRoute->public_key]]);

            // Assert
            $response->assertForbidden();
            $this->assertNull($this->teamIdOf($dungeonRoute));
        } finally {
            $dungeonRoute->delete();
            $outsider->delete();
        }
    }

    #[Test]
    public function addRoutes_givenAGuest_returnsUnauthorized(): void
    {
        // Arrange
        $dungeonRoute = $this->createRoute($this->member);
        Auth::logout();

        try {
            // Act
            $response = $this->post($this->teamRoutesUrl(), ['dungeon_routes' => [$dungeonRoute->public_key]]);

            // Assert
            $response->assertUnauthorized();
            $this->assertNull($this->teamIdOf($dungeonRoute));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenAnUnknownPublicKey_returnsValidationError(): void
    {
        // Arrange
        $dungeonRoute = $this->createRoute($this->member);

        try {
            // Act
            $response = $this->postJson($this->teamRoutesUrl(), [
                'dungeon_routes' => [$dungeonRoute->public_key, 'doesNotExist'],
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('dungeon_routes.1');
            $this->assertNull($this->teamIdOf($dungeonRoute));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function addRoutes_givenNoRoutes_returnsValidationError(): void
    {
        // Arrange - nothing to add

        // Act
        $response = $this->postJson($this->teamRoutesUrl(), ['dungeon_routes' => []]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('dungeon_routes');
    }

    #[Test]
    public function addRoutes_givenMoreRoutesThanAllowedAtOnce_returnsValidationError(): void
    {
        // Arrange
        $publicKeys = array_map(
            static fn(int $index): string => sprintf('key%d', $index),
            range(0, TeamAddRoutesFormRequest::MAX_ROUTES_PER_REQUEST),
        );

        // Act
        $response = $this->postJson($this->teamRoutesUrl(), ['dungeon_routes' => $publicKeys]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('dungeon_routes');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createRoute(User $author, array $attributes = []): DungeonRoute
    {
        return DungeonRoute::factory()->create(array_merge([
            'author_id'  => $author->id,
            'team_id'    => null,
            'expires_at' => null,
        ], $attributes));
    }

    private function teamIdOf(DungeonRoute $dungeonRoute): ?int
    {
        return DungeonRoute::query()->whereKey($dungeonRoute->id)->value('team_id');
    }

    private function createTeam(): Team
    {
        return Team::create([
            'name'         => sprintf('Ajax add routes test %s', uniqid()),
            'public_key'   => Team::generateRandomPublicKey(),
            'invite_code'  => Team::generateRandomPublicKey(12, 'invite_code'),
            'description'  => 'Created by AjaxTeamControllerAddRoutesTest',
            'icon_file_id' => -1,
            'default_role' => TeamUser::ROLE_MEMBER,
        ]);
    }

    private function teamRoutesUrl(): string
    {
        return sprintf('/ajax/team/%s/route', $this->team->getRouteKey());
    }
}
