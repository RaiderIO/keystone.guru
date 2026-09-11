<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Fixtures\Traits\CreatesNpclessCombatLogDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Inactive expansions, game versions and dungeons used to come back as 403, via DungeonPolicy,
 * ExpansionPolicy, GameVersionPolicy and SeasonPolicy - four one-method policies that were plain
 * `active` checks and never looked at the user. "This content is retired" is a 404: it is gone for
 * everyone, not withheld from you.
 *
 * None of these flip `active` on a live row, so a mid-test failure cannot leave the shared database
 * dirty: the expansion test uses an inactive row the seeder provides, the dungeon test creates its own.
 */
#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteDiscoverInactiveContentTest extends PublicTestCase
{
    use CreatesNpclessCombatLogDungeon;

    #[Test]
    public function discoverExpansion_givenInactiveExpansion_returnsNotFound(): void
    {
        // Arrange
        /** @var Expansion $expansion */
        $expansion = Expansion::where('active', 0)->firstOrFail();

        // Act
        $response = $this->get(route('dungeonroutes.expansion', ['expansion' => $expansion]));

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
    }

    #[Test]
    public function discoverExpansion_givenActiveExpansion_returnsOk(): void
    {
        // Arrange - the counterpart, so the 404 above cannot be passing for an unrelated reason
        /** @var Expansion $expansion */
        $expansion = Expansion::where('active', 1)->firstOrFail();

        // Act
        $response = $this->get(route('dungeonroutes.expansion', ['expansion' => $expansion]));

        // Assert
        $response->assertOk();
    }

    #[Test]
    public function discoverDungeon_givenInactiveDungeon_returnsNotFound(): void
    {
        // Arrange
        /** @var GameVersion $gameVersion */
        $gameVersion = GameVersion::where('active', 1)->firstOrFail();
        $dungeon     = null;

        try {
            $dungeon = $this->createDungeonWithoutNpcs(4575001, 'test_inactive_dungeon_discover');

            // Act
            $response = $this->get(route('dungeonroutes.discoverdungeon', [
                'gameVersion' => $gameVersion,
                'dungeon'     => $dungeon,
            ]));

            // Assert
            $response->assertStatus(StatusCode::NOT_FOUND);
        } finally {
            $this->deleteDungeon($dungeon);
        }
    }

    #[Test]
    public function discoverSeason_givenUnknownSeasonIndex_returnsNotFound(): void
    {
        // Arrange - the season is resolved by query and may be null. That used to reach
        // Gate::authorize('view', null).
        /** @var GameVersion $gameVersion */
        $gameVersion = GameVersion::where('active', 1)->where('has_seasons', 1)->firstOrFail();

        // Act
        $response = $this->get(route('dungeonroutes.season', [
            'gameVersion' => $gameVersion,
            'season'      => 9999,
        ]));

        // Assert
        $response->assertStatus(StatusCode::NOT_FOUND);
    }
}
