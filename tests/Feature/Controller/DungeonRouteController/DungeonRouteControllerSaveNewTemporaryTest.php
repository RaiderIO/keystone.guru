<?php

namespace Tests\Feature\Controller\DungeonRouteController;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('Controller')]
#[Group('DungeonRoute')]
#[Group('SaveNewTemporary')]
final class DungeonRouteControllerSaveNewTemporaryTest extends DungeonRouteControllerCreateTestBase
{
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
}
