<?php

namespace Tests\Feature\View;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\Dungeon\DungeonServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The dungeon context's "More" tile leads to the full dungeon selection. On retail it only exists where the
 * strip is capped, but every other game version always offers it - in the desktop strip and the mobile dropdown.
 */
#[Group('View')]
#[Group('DungeonContext')]
final class DungeonContextMoreTest extends PublicTestCase
{
    private const string DESKTOP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    #[Test]
    public function home_givenAGuestOnRetail_omitsTheMoreTile(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $this->assertStringNotContainsString($this->selectUrl(GameVersion::GAME_VERSION_RETAIL), $response->getContent());
    }

    #[Test]
    #[DataProvider('nonRetailGameVersionProvider')]
    public function home_givenAGuestOnANonRetailGameVersion_showsTheMoreTileInBothSelectors(string $gameVersionKey): void
    {
        // Arrange
        $this->actingAsGuest();
        $_COOKIE['game_version'] = $gameVersionKey;

        try {
            // Act
            $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

            // Assert
            $response->assertOk();
            $this->assertSame(2, substr_count($response->getContent(), $this->selectUrl($gameVersionKey)));
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonRetailGameVersionProvider(): array
    {
        return [
            'classic era, more dungeons than the strip holds' => [GameVersion::GAME_VERSION_CLASSIC_ERA],
            'cata, fewer dungeons than the strip holds'       => [GameVersion::GAME_VERSION_CATA],
        ];
    }

    #[Test]
    public function explore_givenAGuestOnANonRetailGameVersion_keepsThePageOwnMoreLink(): void
    {
        // Arrange
        $this->actingAsGuest();
        $mop = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_MOP);
        /** @var Dungeon|null $dungeon */
        $dungeon = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($mop)
            ->first(static fn(Dungeon $dungeon) => $dungeon->active && $dungeon->getCurrentMappingVersionForGameVersion($mop) !== null);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_MOP;

        $this->assertNotNull($dungeon, 'Need a seeded MoP dungeon with a MoP mapping version');

        try {
            // Act
            $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->followingRedirects()->get(route('dungeon.explore.gameversion.view', [
                'gameVersion' => $mop,
                'dungeon'     => $dungeon,
            ]));

            // Assert
            $response->assertOk();
            $html = $response->getContent();
            $this->assertStringContainsString('id="map_header"', $html, 'Expected the map, not the selection page');
            $this->assertSame(2, substr_count($html, $this->selectUrl(GameVersion::GAME_VERSION_MOP)));
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function render_givenAlwaysShowMoreAndFewerDungeonsThanTheCap_showsTheMoreCard(): void
    {
        // Arrange
        $params = $this->listParams();

        // Act
        $html = view('common.dungeon.list', [...$params, 'alwaysShowMore' => true])->render();

        // Assert
        $this->assertStringContainsString('/more-link', $html);
    }

    #[Test]
    public function render_givenNoAlwaysShowMoreAndFewerDungeonsThanTheCap_omitsTheMoreCard(): void
    {
        // Arrange
        $params = $this->listParams();

        // Act
        $html = view('common.dungeon.list', $params)->render();

        // Assert
        $this->assertStringNotContainsString('/more-link', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function listParams(): array
    {
        $cata = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CATA);

        return [
            'gameVersion'     => $cata,
            'dungeons'        => app(DungeonServiceInterface::class)->getDungeonsForGameVersion($cata),
            'useAbbreviation' => true,
            'selectable'      => true,
            'showMore'        => true,
            'links'           => collect(['more' => '/more-link']),
        ];
    }

    private function selectUrl(string $gameVersionKey): string
    {
        return route('dungeon.explore.gameversion.select', ['gameVersion' => $gameVersionKey]);
    }
}
