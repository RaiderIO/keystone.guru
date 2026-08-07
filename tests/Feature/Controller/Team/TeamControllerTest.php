<?php

namespace Tests\Feature\Controller\Team;

use App\Models\Laratrust\Role;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('Team')]
final class TeamControllerTest extends PublicTestCase
{
    private Team $team;

    private TeamUser $teamUser;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::create([
            'public_key'               => Team::generateRandomPublicKey(),
            'name'                     => 'Test Team',
            'description'              => '',
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'default_role'             => TeamUser::ROLE_MEMBER,
            'route_publishing_enabled' => false,
        ]);

        $this->teamUser = TeamUser::create([
            'team_id' => $this->team->id,
            'user_id' => 1,
            'role'    => TeamUser::ROLE_ADMIN,
        ]);

        $this->actingAs(User::findOrFail(1));
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->teamUser->delete();
            $this->team->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function getRouteKey_givenTeam_returnsPublicKeySlugFormat(): void
    {
        // Act
        $routeKey = $this->team->getRouteKey();

        // Assert
        $this->assertSame(
            sprintf('%s-%s', $this->team->public_key, Str::slug($this->team->name)),
            $routeKey,
        );
    }

    #[Test]
    public function resolveRouteBinding_givenPublicKeySlugValue_resolvesTeam(): void
    {
        // Act
        $resolved = new Team()->resolveRouteBinding($this->team->getRouteKey());

        // Assert
        $this->assertNotNull($resolved);
        $this->assertSame($this->team->id, $resolved->id);
    }

    #[Test]
    public function resolveRouteBinding_givenPublicKeyOnly_resolvesTeam(): void
    {
        // Act
        $resolved = new Team()->resolveRouteBinding($this->team->public_key);

        // Assert
        $this->assertNotNull($resolved);
        $this->assertSame($this->team->id, $resolved->id);
    }

    #[Test]
    public function edit_givenCorrectSlugUrl_returnsOk(): void
    {
        // Act
        $response = $this->get(route('team.edit', $this->team));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function edit_givenPublicKeyOnlyUrl_redirectsToCanonicalUrl(): void
    {
        // Act
        $response = $this->get(sprintf('/team/%s', $this->team->public_key));

        // Assert
        $response->assertRedirect(route('team.edit', $this->team));
        $response->assertStatus(301);
    }

    #[Test]
    public function edit_givenWrongSlug_redirectsToCanonicalUrl(): void
    {
        // Act
        $response = $this->get(sprintf('/team/%s-wrong-slug', $this->team->public_key));

        // Assert
        $response->assertRedirect(route('team.edit', $this->team));
        $response->assertStatus(301);
    }

    #[Test]
    public function createTag_givenTeamMember_createsTheTag(): void
    {
        $tagName = sprintf('test-team-tag-%s', fake()->uuid());

        try {
            // Act
            $response = $this->post(route('team.tag.create', $this->team), ['tag_name_new' => $tagName]);

            // Assert
            $response->assertRedirect();
            $this->assertDatabaseHas('tags', [
                'context_id'      => $this->team->id,
                'context_class'   => Team::class,
                'tag_category_id' => TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM],
                'name'            => $tagName,
            ]);
        } finally {
            Tag::where('name', $tagName)->delete();
        }
    }

    #[Test]
    public function createTag_givenNonMember_returnsForbidden(): void
    {
        // The tag manager posts to a team resolved from the URL by its public key. Nothing else in
        // this method looks at who is asking, so without the gate any authenticated user who knows
        // a team's public key can add a tag definition to that team's tag manager.
        $outsider = null;
        $tagName  = sprintf('test-team-tag-%s', fake()->uuid());

        try {
            // Arrange - the route group is behind 'role:user|admin', so a roleless factory user
            // would be rejected by that middleware and never reach the gate under test
            $outsider = User::factory()->create();
            $outsider->addRole(Role::ROLE_USER);

            // Act
            $response = $this->actingAs($outsider)
                ->post(route('team.tag.create', $this->team), ['tag_name_new' => $tagName]);

            // Assert
            $response->assertForbidden();
            $this->assertDatabaseMissing('tags', ['name' => $tagName]);
        } finally {
            Tag::where('name', $tagName)->delete();
            $outsider?->delete();
        }
    }

    #[Test]
    public function createTag_givenGuest_redirectsToLogin(): void
    {
        $tagName = sprintf('test-team-tag-%s', fake()->uuid());

        try {
            // Arrange - setUp() logs in as user 1, so undo that to actually be a guest
            Auth::logout();

            // Act
            $response = $this->post(route('team.tag.create', $this->team), ['tag_name_new' => $tagName]);

            // Assert
            $response->assertRedirect(route('login'));
            $this->assertDatabaseMissing('tags', ['name' => $tagName]);
        } finally {
            Tag::where('name', $tagName)->delete();
        }
    }
}
