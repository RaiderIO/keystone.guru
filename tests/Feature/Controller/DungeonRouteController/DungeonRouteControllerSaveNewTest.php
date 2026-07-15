<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\Laratrust\Role;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('DungeonRoute')]
#[Group('SaveNew')]
final class DungeonRouteControllerSaveNewTest extends DungeonRouteControllerCreateTestBase
{
    #[Test]
    public function saveNew_givenValidData_createsRouteForUser(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id'          => $dungeon->id,
                'dungeon_route_title' => 'My route',
                'faction_id'          => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($user->id, $dungeonRoute->author_id);
            $this->assertSame('My route', $dungeonRoute->title);
            // Non-temporary routes do not expire
            $this->assertNull($dungeonRoute->expires_at);
        } finally {
            $dungeonRoute?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenDescriptionWithScript_persistsSanitizedDescription(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id'                => $dungeon->id,
                'dungeon_route_description' => 'Hello<script>alert(1)</script>world',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertStringNotContainsStringIgnoringCase('<script', (string)$dungeonRoute->description);
            $this->assertStringContainsString('Hello', (string)$dungeonRoute->description);
        } finally {
            $dungeonRoute?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenDungeonRequiringFactionWithoutFaction_returnsValidationError(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $dungeon = $this->getFactionSelectionRequiredDungeon();

        try {
            // Act
            // Unspecified is the browser default, which is not a valid choice for a faction-required dungeon
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                'faction_id' => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            ]);

            // Assert
            $response->assertSessionHasErrors('faction_id');
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenDungeonRequiringFactionWithFaction_createsRoute(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $dungeon = $this->getFactionSelectionRequiredDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                'faction_id' => Faction::ALL[Faction::FACTION_ALLIANCE],
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame(Faction::ALL[Faction::FACTION_ALLIANCE], $dungeonRoute->faction_id);
        } finally {
            $dungeonRoute?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenNonexistentFactionId_returnsValidationError(): void
    {
        // Arrange
        $user          = User::factory()->create();
        $dungeon       = $this->getActiveDungeon();
        $nonexistentId = (int)Faction::query()->max('id') + 1000;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                'faction_id' => $nonexistentId,
            ]);

            // Assert
            $response->assertSessionHasErrors('faction_id');
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenOwnTeamId_createsRouteWithTeam(): void
    {
        // Arrange
        $user = User::factory()->create();
        $team = $this->createTeam();
        $this->addTeamMember($team, $user);
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                'team_id'    => $team->id,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($team->id, $dungeonRoute->team_id);
        } finally {
            $dungeonRoute?->delete();
            TeamUser::query()->where('team_id', $team->id)->delete();
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenForeignTeamId_returnsValidationError(): void
    {
        // Arrange
        $user = User::factory()->create();
        // A team the acting user is not a member of
        $team    = $this->createTeam();
        $dungeon = $this->getActiveDungeon();

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                'team_id'    => $team->id,
            ]);

            // Assert
            $response->assertSessionHasErrors('team_id');
        } finally {
            $team->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenUnsetTeamId_createsRouteWithoutTeam(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            // -1 is the "no team" sentinel the form submits
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                'team_id'    => -1,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->team_id);
        } finally {
            $dungeonRoute?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenInactiveDungeon_returnsValidationError(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $dungeon = $this->getInactiveDungeon();

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
            ]);

            // Assert
            $response->assertSessionHasErrors('dungeon_id');
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenMissingDungeonId_returnsValidationError(): void
    {
        // Arrange
        $user = User::factory()->create();

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), []);

            // Assert
            $response->assertSessionHasErrors('dungeon_id');
        } finally {
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenSpeedrunDungeonNonEnabledDifficulty_fallsBackToFirstEnabled(): void
    {
        // Arrange
        $user       = User::factory()->create();
        $dungeon    = $this->getActiveSpeedrunDungeon();
        $enabled    = $dungeon->getEnabledSpeedrunDifficulties();
        $notEnabled = $this->firstDifficultyNotEnabledFor($dungeon);
        $sinceId    = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id'         => $dungeon->id,
                'dungeon_difficulty' => $notEnabled,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($enabled[0], $dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNew_givenAdminDemoField_marksRouteAsDemo(): void
    {
        // Arrange
        $admin = User::findOrFail(1);
        $this->assertTrue($admin->hasRole(Role::ROLE_ADMIN), 'User id=1 must be admin (seed the DB).');
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($admin)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                'demo'       => 1,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertTrue((bool)$dungeonRoute->demo);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[DataProvider('submitOptionalEmptyFieldProvider')]
    public function saveNew_givenEmptyOptionalField_createsRoute(string $field): void
    {
        // Arrange
        // A real browser submits "" for empty inputs/selects; ConvertEmptyStringsToNull turns these into null
        $user    = User::factory()->create();
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.savenew'), [
                'dungeon_id' => $dungeon->id,
                $field       => '',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
        } finally {
            $dungeonRoute?->delete();
            $user->delete();
        }
    }

    /**
     * The optional fields a real browser can submit as "" (empty text inputs, empty Tom Select selects, or
     * nullable flag inputs the JS may leave empty). Non-nullable fields the browser never submits empty are
     * deliberately excluded, because for those an empty value is a genuine validation error rather than a
     * browser-contract case:
     *  - dungeon_route_level: a hidden input always pre-populated with a valid "min;max" range,
     *  - dungeon_route_sandbox: not rendered by the create form at all,
     *  - faction_id: a native select that always submits a selected faction id (defaulting to unspecified),
     *  - route_select_affixes: submitted as an array (route_select_affixes[]) or omitted, never as scalar "".
     *
     * @return array<string, array{string}>
     */
    public static function submitOptionalEmptyFieldProvider(): array
    {
        return [
            'dungeon_route_title'        => ['dungeon_route_title'],
            'dungeon_route_description'  => ['dungeon_route_description'],
            'team_id'                    => ['team_id'],
            'teeming'                    => ['teeming'],
            'template'                   => ['template'],
            'pull_gradient'              => ['pull_gradient'],
            'pull_gradient_apply_always' => ['pull_gradient_apply_always'],
            'seasonal_index'             => ['seasonal_index'],
            'race'                       => ['race'],
            'class'                      => ['class'],
            'unlisted'                   => ['unlisted'],
            'dungeon_difficulty'         => ['dungeon_difficulty'],
            'dungeon_start_map_icon_id'  => ['dungeon_start_map_icon_id'],
        ];
    }

    private function createTeam(): Team
    {
        return Team::create([
            'public_key'               => Team::generateRandomPublicKey(),
            'name'                     => 'Test Team',
            'description'              => '',
            'invite_code'              => Team::generateRandomPublicKey(12, 'invite_code'),
            'default_role'             => TeamUser::ROLE_MEMBER,
            'route_publishing_enabled' => true,
        ]);
    }

    private function addTeamMember(Team $team, User $user): void
    {
        TeamUser::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role'    => TeamUser::ROLE_MEMBER,
        ]);
    }
}
