<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Logic\MapContext\Map\MapContextDungeonExplore;
use App\Models\Dungeon;
use App\Models\DungeonStart;
use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonExplore')]
#[Group('DungeonStart')]
final class DungeonExploreControllerNavigateDungeonStartTest extends PublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function navigateDungeonStart_givenStartWithTarget_redirectsToTheTargetsExplorePage(): void
    {
        // Arrange
        $continent    = $this->createClassicDungeon();
        $target       = $this->createClassicDungeon();
        $dungeonStart = $this->createDungeonStart($continent, $target);

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.start.navigate', [
            'gameVersion'  => GameVersion::GAME_VERSION_CLASSIC_ERA,
            'dungeonStart' => $dungeonStart,
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.view', [
            'gameVersion' => GameVersion::GAME_VERSION_CLASSIC_ERA,
            'dungeon'     => $target,
        ]));
    }

    #[Test]
    public function navigateDungeonStart_givenStartLeadingNowhere_returnsNotFound(): void
    {
        // Arrange
        $dungeon      = $this->createClassicDungeon();
        $dungeonStart = $this->createDungeonStart($dungeon, null);

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.start.navigate', [
            'gameVersion'  => GameVersion::GAME_VERSION_CLASSIC_ERA,
            'dungeonStart' => $dungeonStart,
        ]));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function viewDungeonFloor_givenStartWithTarget_hasTheStartNavigateInTheMapContext(): void
    {
        // Arrange
        $continent    = $this->createClassicDungeon();
        $target       = $this->createClassicDungeon();
        $dungeonStart = $this->createDungeonStart($continent, $target);

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => GameVersion::GAME_VERSION_CLASSIC_ERA,
            'dungeon'     => $continent,
            'floorIndex'  => 1,
        ]));

        // Assert
        $response->assertOk();
        /** @var MapContextDungeonExplore $mapContext */
        $mapContext             = $response->viewData('mapContext');
        $dungeonStartNavigation = $mapContext->toArray()['dungeonStartNavigation'];
        $this->assertSame([$dungeonStart->id], array_keys($dungeonStartNavigation));
        $this->assertSame(route('dungeon.explore.gameversion.start.navigate', [
            'gameVersion'  => GameVersion::GAME_VERSION_CLASSIC_ERA,
            'dungeonStart' => $dungeonStart,
        ]), $dungeonStartNavigation[$dungeonStart->id]['url']);
    }

    private function createClassicDungeon(): Dungeon
    {
        return $this->createDungeon(['active' => true], mappingVersionAttributes: [
            'game_version_id' => GameVersion::query()->where('key', GameVersion::GAME_VERSION_CLASSIC_ERA)->firstOrFail()->id,
        ]);
    }

    /**
     * Deleted with its mapping version when the test's dungeons are torn down.
     */
    private function createDungeonStart(Dungeon $dungeon, ?Dungeon $targetDungeon): DungeonStart
    {
        return DungeonStart::factory()->create([
            'mapping_version_id' => $dungeon->mappingVersions()->firstOrFail()->id,
            'floor_id'           => $dungeon->floors()->firstOrFail()->id,
            'target_dungeon_id'  => $targetDungeon?->id,
        ]);
    }
}
