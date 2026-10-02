<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('DungeonExplore')]
final class DungeonMergedSlugRedirectTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('mergedSlugProvider')]
    public function resolveRouteBinding_givenMergedDungeonSlug_redirectsToTheDungeonItWasMergedInto(
        string $mergedSlug,
        string $mergedIntoSlug,
    ): void {
        // Arrange
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_SOD)->firstOrFail();
        $dungeon     = Dungeon::query()->where('slug', $mergedIntoSlug)->firstOrFail();
        $expectedUrl = route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => 1,
        ]);

        // Act
        $response = $this->get(sprintf('%s?embed=1', str_replace($mergedIntoSlug, $mergedSlug, $expectedUrl)));

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(sprintf('%s?embed=1', $expectedUrl));
    }

    /** @return array<string, array{string, string}> */
    public static function mergedSlugProvider(): array
    {
        return [
            'ruins of ahn\'qiraj'  => ['ruins-of-ahnqiraj-sod', 'ruins-of-ahnqiraj-classic'],
            'temple of ahn\'qiraj' => ['temple-of-ahnqiraj-sod', 'temple-of-ahnqiraj-classic'],
        ];
    }

    #[Test]
    public function resolveRouteBinding_givenMergedDungeonSlugOnRouteUrl_redirectsOnlyTheDungeonSegment(): void
    {
        // Arrange
        $url = '/route/temple-of-ahnqiraj-sod/AbCdEfG/temple-of-ahnqiraj-sod';

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertStatus(301);
        $response->assertRedirect(url('/route/temple-of-ahnqiraj-classic/AbCdEfG/temple-of-ahnqiraj-sod'));
    }

    #[Test]
    public function resolveRouteBinding_givenUnknownDungeonSlug_returnsNotFound(): void
    {
        // Arrange
        $gameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_SOD)->firstOrFail();

        // Act
        $response = $this->get(sprintf('/explore/%s/temple-of-ahnqiraj-sod-unknown', $gameVersion->key));

        // Assert
        $response->assertNotFound();
    }
}
