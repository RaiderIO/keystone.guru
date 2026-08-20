<?php

namespace Tests\Feature\View\Dungeonroute\Discover;

use App\Models\Dungeon;
use App\Models\Expansion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Discover')]
final class WallpaperTest extends PublicTestCase
{
    #[Test]
    public function render_givenDungeonWithWallpaper_rendersTheDungeonWallpaper(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('has_wallpaper', true)->firstOrFail();

        // Act
        $result = view('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])->render();

        // Assert
        $this->assertStringContainsString($dungeon->getImageWallpaperUrl(), $result);
    }

    #[Test]
    public function render_givenDungeonWithoutWallpaper_fallsBackToTheExpansionWallpaper(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('has_wallpaper', false)->with('expansion')->firstOrFail();

        // Act
        $result = view('dungeonroute.discover.wallpaper', ['dungeon' => $dungeon])->render();

        // Assert
        $this->assertStringContainsString($dungeon->expansion->getWallpaperUrl(), $result);
        $this->assertStringNotContainsString($dungeon->getImageWallpaperUrl(), $result);
    }

    #[Test]
    public function render_givenExpansion_rendersTheExpansionWallpaper(): void
    {
        // Arrange
        $expansion = Expansion::query()->firstOrFail();

        // Act
        $result = view('dungeonroute.discover.wallpaper', ['expansion' => $expansion])->render();

        // Assert
        $this->assertStringContainsString($expansion->getWallpaperUrl(), $result);
    }

    #[Test]
    public function render_givenNoDungeonExpansionOrGameVersion_rendersNothing(): void
    {
        // Act
        $result = view('dungeonroute.discover.wallpaper', [])->render();

        // Assert
        $this->assertSame('', trim($result));
    }
}
