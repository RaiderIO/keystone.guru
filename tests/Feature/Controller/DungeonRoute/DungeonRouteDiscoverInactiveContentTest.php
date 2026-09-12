<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Teapot\StatusCode;
use Tests\Fixtures\Traits\CreatesExpansion;
use Tests\Fixtures\Traits\CreatesNpclessCombatLogDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteDiscoverInactiveContentTest extends PublicTestCase
{
    use CreatesExpansion;
    use CreatesNpclessCombatLogDungeon;

    #[Test]
    public function discoverExpansion_givenInactiveExpansion_returnsNotFound(): void
    {
        // Arrange - an expansion of our own; the seed promises active expansions, not a retired one
        $expansion = $this->createExpansion(['active' => false]);

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
        // Arrange
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
