<?php

namespace Tests\Feature\Controller;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\Laratrust\Role;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteControllerTest extends PublicTestCase
{
    // region saveNewTemporary (guest-accessible)

    #[Test]
    public function saveNewTemporary_givenActiveDungeon_createsGuestRoute(): void
    {
        // Arrange
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id' => $dungeon->id,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($dungeon->id, $dungeonRoute->dungeon_id);
            // A guest is author -1
            $this->assertSame(-1, $dungeonRoute->author_id);
            // Temporary routes expire and get a generated title
            $this->assertNotNull($dungeonRoute->expires_at);
            $this->assertNotEmpty($dungeonRoute->title);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenLoggedInUser_setsAuthorIdToUser(): void
    {
        // Arrange
        $user    = User::factory()->create();
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->actingAs($user)->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id' => $dungeon->id,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($user->id, $dungeonRoute->author_id);
        } finally {
            $dungeonRoute?->delete();
            $user->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenHasSeasonsGameVersion_setsSeasonId(): void
    {
        // Arrange
        // A guest defaults to the retail game version, which has seasons
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id' => $dungeon->id,
            ]);

            // Assert
            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            // The model PHPDoc types season_id as a non-nullable int, so read the raw value to assert it was set
            $this->assertNotNull($dungeonRoute->getRawOriginal('season_id'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenEmptyDungeonDifficulty_createsRoute(): void
    {
        // Arrange
        // The difficulty select is empty for non-speedrun dungeons - Tom Select then submits an empty value for it
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id'         => $dungeon->id,
                'dungeon_difficulty' => '',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();
            $response->assertRedirect();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenSpeedrunDungeonEnabledDifficulty_keepsDifficulty(): void
    {
        // Arrange
        $dungeon = $this->getActiveSpeedrunDungeon();
        $enabled = $dungeon->getEnabledSpeedrunDifficulties();
        // Pick an enabled difficulty that is NOT the fallback (first enabled), so "kept" is distinguishable
        $chosen  = $enabled[1];
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id'         => $dungeon->id,
                'dungeon_difficulty' => $chosen,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($chosen, $dungeonRoute->getRawOriginal('dungeon_difficulty'));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenSpeedrunDungeonNonEnabledDifficulty_fallsBackToFirstEnabled(): void
    {
        // Arrange
        $dungeon    = $this->getActiveSpeedrunDungeon();
        $enabled    = $dungeon->getEnabledSpeedrunDifficulties();
        $notEnabled = $this->firstDifficultyNotEnabledFor($dungeon);
        $sinceId    = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
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
        }
    }

    #[Test]
    #[DataProvider('validDungeonDifficultyProvider')]
    public function saveNewTemporary_givenValidDungeonDifficulty_createsRoute(int $difficulty): void
    {
        // Arrange
        $dungeon = $this->getActiveSpeedrunDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id'         => $dungeon->id,
                'dungeon_difficulty' => $difficulty,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    /** @return array<string, array{int}> */
    public static function validDungeonDifficultyProvider(): array
    {
        return [
            '10 man' => [Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_10_MAN]],
            '25 man' => [Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_25_MAN]],
            '20 man' => [Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_20_MAN]],
            '40 man' => [Dungeon::DIFFICULTY_ALL[Dungeon::DIFFICULTY_40_MAN]],
        ];
    }

    #[Test]
    #[DataProvider('invalidDungeonDifficultyProvider')]
    public function saveNewTemporary_givenInvalidDungeonDifficulty_returnsValidationError(mixed $difficulty): void
    {
        // Arrange
        $dungeon = $this->getActiveSpeedrunDungeon();

        // Act
        $response = $this->post(route('dungeonroute.temporary.savenew'), [
            'dungeon_id'         => $dungeon->id,
            'dungeon_difficulty' => $difficulty,
        ]);

        // Assert
        $response->assertSessionHasErrors('dungeon_difficulty');
    }

    /** @return array<string, array{mixed}> */
    public static function invalidDungeonDifficultyProvider(): array
    {
        return [
            'zero'            => [0],
            'out of range'    => [99],
            'difficulty slug' => [Dungeon::DIFFICULTY_10_MAN],
        ];
    }

    #[Test]
    public function saveNewTemporary_givenMissingDungeonId_returnsValidationError(): void
    {
        // Arrange + Act
        $response = $this->post(route('dungeonroute.temporary.savenew'), []);

        // Assert
        $response->assertSessionHasErrors('dungeon_id');
    }

    #[Test]
    public function saveNewTemporary_givenNonexistentDungeonId_returnsValidationError(): void
    {
        // Arrange
        $nonexistentId = (int)Dungeon::query()->max('id') + 1000;

        // Act
        $response = $this->post(route('dungeonroute.temporary.savenew'), [
            'dungeon_id' => $nonexistentId,
        ]);

        // Assert
        $response->assertSessionHasErrors('dungeon_id');
    }

    #[Test]
    public function saveNewTemporary_givenInactiveDungeon_returnsValidationError(): void
    {
        // Arrange
        $dungeon = $this->getInactiveDungeon();

        // Act
        $response = $this->post(route('dungeonroute.temporary.savenew'), [
            'dungeon_id' => $dungeon->id,
        ]);

        // Assert
        $response->assertSessionHasErrors('dungeon_id');
    }

    #[Test]
    public function saveNewTemporary_givenValidDungeonRouteLevel_setsLevelRange(): void
    {
        // Arrange
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id'          => $dungeon->id,
                'dungeon_route_level' => '5;12',
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame(5, $dungeonRoute->level_min);
            $this->assertSame(12, $dungeonRoute->level_max);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenMalformedDungeonRouteLevel_returnsValidationError(): void
    {
        // Arrange
        $dungeon = $this->getActiveDungeon();

        // Act
        $response = $this->post(route('dungeonroute.temporary.savenew'), [
            'dungeon_id'          => $dungeon->id,
            'dungeon_route_level' => 'not-a-range',
        ]);

        // Assert
        $response->assertSessionHasErrors('dungeon_route_level');
    }

    #[Test]
    public function saveNewTemporary_givenValidStartMapIcon_setsStartMapIconId(): void
    {
        // Arrange
        [$dungeon, $mapIcon] = $this->getDungeonWithStartIcon();
        $sinceId             = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id'                => $dungeon->id,
                'dungeon_start_map_icon_id' => $mapIcon->id,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertSame($mapIcon->id, $dungeonRoute->dungeon_start_map_icon_id);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function saveNewTemporary_givenForeignStartMapIcon_resolvesToNull(): void
    {
        // Arrange
        // A map icon id that is not a dungeon start of the posted dungeon's mapping version must not error, it resolves to null
        $dungeon       = $this->getActiveDungeon();
        $foreignIconId = (int)MapIcon::query()->max('id') + 1000;
        $sinceId       = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
                'dungeon_id'                => $dungeon->id,
                'dungeon_start_map_icon_id' => $foreignIconId,
            ]);

            // Assert
            $response->assertSessionHasNoErrors();

            $dungeonRoute = $this->latestRouteSince($sinceId);
            $this->assertNotNull($dungeonRoute);
            $this->assertNull($dungeonRoute->dungeon_start_map_icon_id);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    #[DataProvider('temporaryOptionalEmptyFieldProvider')]
    public function saveNewTemporary_givenEmptyOptionalField_createsRoute(string $field): void
    {
        // Arrange
        // A real browser submits "" for empty inputs/selects; ConvertEmptyStringsToNull turns these into null
        $dungeon = $this->getActiveDungeon();
        $sinceId = (int)DungeonRoute::query()->max('id');

        $dungeonRoute = null;

        try {
            // Act
            $response = $this->post(route('dungeonroute.temporary.savenew'), [
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
        }
    }

    /**
     * The optional scalar fields a real browser can submit as "". dungeon_route_level is deliberately
     * excluded: it is a hidden input that is always pre-populated with a valid "min;max" range (and only
     * rendered for season game versions), so the browser never submits it empty - and DungeonRouteLevelRule
     * has no nullable, so an empty value is a genuine validation error rather than a browser-contract case.
     *
     * @return array<string, array{string}>
     */
    public static function temporaryOptionalEmptyFieldProvider(): array
    {
        return [
            'dungeon_difficulty'        => ['dungeon_difficulty'],
            'dungeon_start_map_icon_id' => ['dungeon_start_map_icon_id'],
        ];
    }

    // endregion

    // region saveNew (authenticated)

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

    // endregion

    // region throttling

    #[Test]
    public function saveNewTemporary_givenTooManyRequests_isRateLimited(): void
    {
        // Arrange
        $dungeon = $this->getActiveDungeon();
        $created = [];
        // Drive the create-dungeonroute limiter down to one/hour so a couple of requests prove the
        // throttle middleware and limiter registration are wired up, without creating a hundred rows
        $this->overrideHttpRateLimit(1);

        try {
            // Act
            $lastResponse = null;
            for ($i = 0; $i < 3; ++$i) {
                $sinceId      = (int)DungeonRoute::query()->max('id');
                $lastResponse = $this->post(route('dungeonroute.temporary.savenew'), [
                    'dungeon_id' => $dungeon->id,
                ]);

                $route = $this->latestRouteSince($sinceId);
                if ($route !== null) {
                    $created[] = $route;
                }

                if ($lastResponse->status() === 429) {
                    break;
                }
            }

            // Assert
            $this->assertSame(429, $lastResponse->status());
        } finally {
            $this->overrideHttpRateLimit(null);
            foreach ($created as $route) {
                $route->delete();
            }
        }
    }

    // endregion

    // region helpers

    private function overrideHttpRateLimit(?int $limit): void
    {
        $property = new \ReflectionProperty(\App\Providers\AppServiceProvider::class, 'rateLimitOverrideHttp');
        $property->setValue(null, $limit);
    }

    private function latestRouteSince(int $sinceId): ?DungeonRoute
    {
        return DungeonRoute::query()
            ->where('id', '>', $sinceId)
            ->orderByDesc('id')
            ->first();
    }

    private function getActiveDungeon(): Dungeon
    {
        return Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', false)
            ->select('dungeons.*')
            ->firstOrFail();
    }

    private function getActiveSpeedrunDungeon(): Dungeon
    {
        $dungeon = Dungeon::query()
            ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
            ->where('expansions.active', true)
            ->where('dungeons.active', true)
            ->where('dungeons.speedrun_enabled', true)
            ->select('dungeons.*')
            ->with('dungeonSpeedrunDifficulties')
            ->get()
            ->first(static fn(Dungeon $dungeon): bool => count($dungeon->getEnabledSpeedrunDifficulties()) >= 2);

        $this->assertNotNull($dungeon, 'Expected a speedrun dungeon with at least two enabled difficulties.');

        return $dungeon;
    }

    private function firstDifficultyNotEnabledFor(Dungeon $dungeon): int
    {
        $enabled = $dungeon->getEnabledSpeedrunDifficulties();
        // A valid enum difficulty that is not one of this dungeon's enabled speedrun difficulties
        $notEnabled = collect(array_values(Dungeon::DIFFICULTY_ALL))
            ->first(static fn(int $difficulty): bool => !in_array($difficulty, $enabled, true));

        $this->assertNotNull($notEnabled, 'Expected a valid difficulty that is not enabled for this dungeon.');

        return (int)$notEnabled;
    }

    private function getInactiveDungeon(): Dungeon
    {
        return Dungeon::query()->where('active', false)->firstOrFail();
    }

    private function getFactionSelectionRequiredDungeon(): Dungeon
    {
        return Dungeon::factionSelectionRequired()->where('active', true)->firstOrFail();
    }

    /**
     * @return array{0: Dungeon, 1: MapIcon}
     */
    private function getDungeonWithStartIcon(): array
    {
        $mapIcon = MapIcon::query()
            ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
            ->whereNotNull('mapping_version_id')
            ->whereHas('mappingVersion.dungeon', static function ($query): void {
                $query->where('active', true);
            })
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($mapIcon, 'Expected a seeded dungeon start map icon.');

        $dungeon = $mapIcon->mappingVersion->dungeon;
        // Only usable when the icon lives on the dungeon's current mapping version
        $this->assertSame($dungeon->getCurrentMappingVersion()->id, $mapIcon->mapping_version_id);

        return [$dungeon, $mapIcon];
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

    // endregion
}
