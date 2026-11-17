<?php

namespace Tests\Feature\App\Http\Middleware;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Middleware')]
final class SetsFramingAndReferrerHeadersTest extends PublicTestCase
{
    #[Test]
    public function handle_givenRegularPage_onlyAllowsFramingBySameOrigin(): void
    {
        // Act
        $response = $this->get(route('home'));

        // Assert
        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    #[Test]
    public function handle_givenEmbedPage_allowsFramingByOtherSites(): void
    {
        // Arrange
        $gameVersion = GameVersion::getUserOrDefaultGameVersion();
        $dungeon     = Dungeon::getUserOrDefaultDungeon();

        // Act
        $response = $this->get(route('dungeon.explore.gameversion.embed.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => '1',
        ]));

        // Assert - the default dungeon may redirect to the dungeon select; the headers apply either way
        $response->assertHeaderMissing('X-Frame-Options');
        $response->assertHeaderMissing('Content-Security-Policy');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    #[Test]
    public function handle_givenEmbedWrapperPage_allowsFramingByOtherSites(): void
    {
        // Arrange
        $gameVersion = GameVersion::getUserOrDefaultGameVersion();
        $dungeon     = Dungeon::getUserOrDefaultDungeon();

        // Act
        $response = $this->get(route('misc.embed.explore', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]));

        // Assert
        $response->assertOk();
        $response->assertHeaderMissing('X-Frame-Options');
        $response->assertHeaderMissing('Content-Security-Policy');
    }
}
