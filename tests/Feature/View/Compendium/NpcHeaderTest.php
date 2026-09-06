<?php

namespace Tests\Feature\View\Compendium;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Compendium')]
final class NpcHeaderTest extends PublicTestCase
{
    #[Test]
    public function render_givenNpc_linksToItsWowheadPage(): void
    {
        // Arrange
        $npc = Npc::with(['classification'])->firstOrFail();

        // Act
        $result = view('compendium.npc.sections.header', ['npc' => $npc, 'currentNpcHealth' => null])->render();

        // Assert
        $this->assertStringContainsString($npc->wowhead_url, $result);
    }

    #[Test]
    public function wowheadUrl_givenRetailNpc_slugsTheName(): void
    {
        // Arrange
        $npc                  = new Npc(['name' => 'Forgemaster Garfrost']);
        $npc->id              = 36494;
        $npc->game_version_id = GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL];

        // Act
        $result = $npc->wowhead_url;

        // Assert
        $this->assertSame('https://www.wowhead.com/npc=36494/forgemaster-garfrost', $result);
    }

    #[Test]
    #[DataProvider('gameVersionWowheadUrlProvider')]
    public function wowheadUrl_givenGameVersion_linksToThatGameVersionsWowheadDatabase(
        string $gameVersionKey,
        string $expectedUrl,
    ): void {
        // Arrange
        $npc                  = new Npc(['name' => 'Sha of Doubt']);
        $npc->id              = 56439;
        $npc->game_version_id = GameVersion::ALL[$gameVersionKey];

        // Act
        $result = $npc->wowhead_url;

        // Assert
        $this->assertSame($expectedUrl, $result);
    }

    /**
     * Cata and Legion Remix have no Wowhead database of their own - those NPCs are looked up on
     * retail Wowhead, so they must not gain a domain prefix.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function gameVersionWowheadUrlProvider(): array
    {
        return [
            'retail'       => [GameVersion::GAME_VERSION_RETAIL, 'https://www.wowhead.com/npc=56439/sha-of-doubt'],
            'classic era'  => [GameVersion::GAME_VERSION_CLASSIC_ERA, 'https://www.wowhead.com/classic/npc=56439/sha-of-doubt'],
            'wrath'        => [GameVersion::GAME_VERSION_WRATH, 'https://www.wowhead.com/wrath/npc=56439/sha-of-doubt'],
            'mop'          => [GameVersion::GAME_VERSION_MOP, 'https://www.wowhead.com/mop-classic/npc=56439/sha-of-doubt'],
            'cata'         => [GameVersion::GAME_VERSION_CATA, 'https://www.wowhead.com/npc=56439/sha-of-doubt'],
            'legion remix' => [GameVersion::GAME_VERSION_LEGION_REMIX, 'https://www.wowhead.com/npc=56439/sha-of-doubt'],
        ];
    }

    #[Test]
    public function render_givenMistsOfPandariaNpc_linksToTheMopClassicWowheadDatabase(): void
    {
        // Arrange
        $npc                  = Npc::with(['classification'])->firstOrFail();
        $npc->game_version_id = GameVersion::ALL[GameVersion::GAME_VERSION_MOP];

        // Act
        $result = view('compendium.npc.sections.header', ['npc' => $npc, 'currentNpcHealth' => null])->render();

        // Assert
        $this->assertStringContainsString(sprintf('https://www.wowhead.com/mop-classic/npc=%d/', $npc->id), $result);
    }
}
