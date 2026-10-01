<?php

namespace Tests\Unit\App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Service\Wowhead\WowheadService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('Wowhead')]
final class WowheadServiceTest extends TestCase
{
    #[Test]
    #[DataProvider('npcPageUrlProvider')]
    public function getNpcPageHtml_givenGameVersion_requestsThatGameVersionsWowheadPage(string $gameVersionKey, string $expectedUrl): void
    {
        // Arrange
        $gameVersion = new GameVersion()->forceFill([
            'id'  => GameVersion::ALL[$gameVersionKey],
            'key' => $gameVersionKey,
        ]);
        $npc = new Npc()->forceFill([
            'id'   => 7800,
            'name' => 'Mekgineer Thermaplugg',
        ]);
        $wowheadService = $this->partialMock(WowheadService::class, function ($mock) use ($expectedUrl) {
            $mock->shouldReceive('curlGet')->once()->with($expectedUrl)->andReturn('<html></html>');
        });

        // Act
        $html = $wowheadService->getNpcPageHtml($gameVersion, $npc);

        // Assert
        $this->assertSame('<html></html>', $html);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function npcPageUrlProvider(): array
    {
        return [
            'retail has no path'          => [GameVersion::GAME_VERSION_RETAIL, 'https://wowhead.com/npc=7800/mekgineer-thermaplugg'],
            'classic era'                 => [GameVersion::GAME_VERSION_CLASSIC_ERA, 'https://wowhead.com/classic/npc=7800/mekgineer-thermaplugg'],
            'season of discovery'         => [GameVersion::GAME_VERSION_SOD, 'https://wowhead.com/classic/npc=7800/mekgineer-thermaplugg'],
            'tbc classic'                 => [GameVersion::GAME_VERSION_TBC, 'https://wowhead.com/tbc/npc=7800/mekgineer-thermaplugg'],
            'wrath uses its wowhead name' => [GameVersion::GAME_VERSION_WRATH, 'https://wowhead.com/wrath/npc=7800/mekgineer-thermaplugg'],
            'no wowhead domain keeps key' => [GameVersion::GAME_VERSION_FOREVER, 'https://wowhead.com/forever/npc=7800/mekgineer-thermaplugg'],
        ];
    }
}
