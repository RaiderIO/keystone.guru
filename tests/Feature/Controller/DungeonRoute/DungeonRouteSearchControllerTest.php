<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonRoute')]
final class DungeonRouteSearchControllerTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function searchByDungeon_givenDungeonWithCurrentMappingVersion_rendersDefaultFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, requireDefaultFloor: true);
        $gameVersion                = GameVersion::findOrFail($mappingVersion->game_version_id);
        /** @var Floor $expectedFloor */
        $expectedFloor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        // Act
        $response = $this->get(route('dungeon.dungeonroute.search.gameversion.dungeon', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('floor', static fn(Floor $floor) => $floor->id === $expectedFloor->id);
    }
}
