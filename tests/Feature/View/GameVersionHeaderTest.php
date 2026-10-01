<?php

namespace Tests\Feature\View;

use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The header's game version row: one link per active game version, the current one marked for assistive tech.
 */
#[Group('View')]
#[Group('GameVersion')]
final class GameVersionHeaderTest extends PublicTestCase
{
    private const string DESKTOP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    #[Test]
    public function home_givenANonRetailGameVersion_marksOnlyThatVersionAsCurrent(): void
    {
        // Arrange
        $this->actingAsGuest();
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_MOP;

        try {
            // Act
            $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

            // Assert
            $response->assertOk();
            $row = $this->gameVersionRow($response->getContent());

            $this->assertSame(1, substr_count($row, 'aria-current="true"'));
            $this->assertMatchesRegularExpression(
                sprintf('#href="%s"\s+aria-current="true"#', preg_quote(route('gameversion.update', ['gameVersion' => GameVersion::GAME_VERSION_MOP]), '#')),
                $row,
            );
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function home_givenAGuest_rendersEachVersionWithItsOwnDecorativeLogoAndMixedCaseName(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $response = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/');

        // Assert
        $response->assertOk();
        $row = $this->gameVersionRow($response->getContent());

        $activeGameVersions = GameVersion::active()->get();
        $this->assertNotEmpty($activeGameVersions);
        $this->assertSame($activeGameVersions->count(), substr_count($row, '<li>'));
        foreach ($activeGameVersions as $gameVersion) {
            $this->assertMatchesRegularExpression(sprintf('#gameversions/%s\.webp[^"]*" alt=""#', preg_quote($gameVersion->key, '#')), $row);
            $this->assertStringContainsString(e(__($gameVersion->name)), $row);
        }
        $this->assertStringContainsString('Classic Era', $row);
        $this->assertStringNotContainsString('CLASSIC ERA', $row);
        $this->assertStringNotContainsString('logo_white_small', $row);
    }

    private function gameVersionRow(string $html): string
    {
        $matched = preg_match(
            sprintf('#<nav aria-label="%s">(.*?)</nav>#s', preg_quote(__('view_common.layout.header.game_versions'), '#')),
            $html,
            $matches,
        );
        $this->assertSame(1, $matched, 'The header should render the game version row as a labelled nav');

        return $matches[1];
    }
}
