<?php

namespace Tests\Feature\Controller;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\Dungeon\DungeonServiceInterface;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('GameVersion')]
final class GameVersionControllerTest extends PublicTestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        try {
            unset($_COOKIE['game_version']);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function update_givenAHeatmapReferer_redirectsToTheHeatmap(): void
    {
        // Arrange
        $referer = route('dungeon.heatmap.gameversion.view', [
            'gameVersion' => $this->getRetailGameVersion(),
            'dungeon'     => $this->getRetailDungeon(),
        ]);

        // Act
        $response = $this->switchToClassicEra($referer);

        // Assert
        $response->assertRedirect(route('dungeon.heatmap'));
    }

    #[Test]
    public function update_givenARouteSearchReferer_redirectsToTheRouteSearch(): void
    {
        // Arrange
        $referer = route('dungeon.dungeonroute.search.gameversion.dungeon', [
            'gameVersion' => $this->getRetailGameVersion(),
            'dungeon'     => $this->getRetailDungeon(),
        ]);

        // Act
        $response = $this->switchToClassicEra($referer);

        // Assert
        $response->assertRedirect(route('dungeon.dungeonroute.search'));
    }

    #[Test]
    public function update_givenARefererOutsideTheGameVersionPages_redirectsBack(): void
    {
        // Arrange
        $referer = route('home');

        // Act
        $response = $this->switchToClassicEra($referer);

        // Assert
        $response->assertRedirect($referer);
    }

    /**
     * @return TestResponse<\Illuminate\Http\RedirectResponse>
     */
    private function switchToClassicEra(string $referer): TestResponse
    {
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_RETAIL;

        return $this->withHeader('referer', $referer)->get(route('gameversion.update', [
            'gameVersion' => GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA),
        ]));
    }

    private function getRetailGameVersion(): GameVersion
    {
        return GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
    }

    private function getRetailDungeon(): Dungeon
    {
        return app(DungeonServiceInterface::class)->getDungeonsForGameVersion($this->getRetailGameVersion())->first();
    }
}
