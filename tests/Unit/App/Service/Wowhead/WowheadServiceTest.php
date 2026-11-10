<?php

namespace Tests\Unit\App\Service\Wowhead;

use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcType;
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

    #[Test]
    public function getNpcPageHtmlByNpcId_givenNpcIdAndName_requestsThatNpcsWowheadPage(): void
    {
        // Arrange
        $gameVersion = new GameVersion()->forceFill([
            'id'  => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'key' => GameVersion::GAME_VERSION_RETAIL,
        ]);
        $wowheadService = $this->partialMock(WowheadService::class, function ($mock) {
            $mock->shouldReceive('curlGet')->once()->with('https://wowhead.com/npc=448/hogger')->andReturn('<html></html>');
        });

        // Act
        $html = $wowheadService->getNpcPageHtmlByNpcId($gameVersion, 448, 'Hogger');

        // Assert
        $this->assertSame('<html></html>', $html);
    }

    #[Test]
    public function getNpcDisplayIdByNpcId_givenNpcPage_returnsItsDisplayId(): void
    {
        // Arrange
        $gameVersion = new GameVersion()->forceFill([
            'id'  => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'key' => GameVersion::GAME_VERSION_RETAIL,
        ]);
        $wowheadService = app(WowheadService::class);

        // Act
        $displayId = $wowheadService->getNpcDisplayIdByNpcId($gameVersion, 210269, 'Hired Muscle', self::npcPageHtml(210269, '{"name":"Hired Muscle"}'));

        // Assert
        $this->assertSame(118999, $displayId);
    }

    #[Test]
    public function getNpcData_givenNpcPage_returnsTheParsedNpcData(): void
    {
        // Arrange
        $html           = self::npcPageHtml(210269, '{"classification":1,"displayName":"Hired Muscle","id":210269,"location":[15103],"name":"Hired Muscle","react":[-1,-1],"type":7}');
        $wowheadService = app(WowheadService::class);

        // Act
        $result = $wowheadService->getNpcData(210269, $html);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame([
            'npc_id'             => 210269,
            'name'               => 'Hired Muscle',
            'display_id'         => 118999,
            'classification_key' => NpcClassification::NPC_CLASSIFICATION_ELITE,
            'npc_type_id'        => NpcType::HUMANOID,
            'zone_ids'           => [15103],
        ], $result->toArray());
    }

    #[Test]
    #[DataProvider('npcClassificationProvider')]
    public function getNpcData_givenWowheadClassification_returnsMappedClassification(string $json, string $expectedClassificationKey): void
    {
        // Arrange
        $wowheadService = app(WowheadService::class);

        // Act
        $result = $wowheadService->getNpcData(210271, self::npcPageHtml(210271, $json));

        // Assert
        $this->assertSame($expectedClassificationKey, $result?->getClassificationKey());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function npcClassificationProvider(): array
    {
        return [
            'normal'                => ['{"classification":0,"name":"A","type":7}', NpcClassification::NPC_CLASSIFICATION_NORMAL],
            'elite'                 => ['{"classification":1,"name":"A","type":7}', NpcClassification::NPC_CLASSIFICATION_ELITE],
            'rare elite'            => ['{"classification":2,"name":"A","type":7}', NpcClassification::NPC_CLASSIFICATION_RARE],
            'boss flag beats elite' => ['{"boss":1,"classification":1,"name":"A","type":7}', NpcClassification::NPC_CLASSIFICATION_BOSS],
        ];
    }

    #[Test]
    #[DataProvider('npcCreatureTypeProvider')]
    public function getNpcData_givenWowheadCreatureType_returnsMappedNpcType(int $wowheadType, int $expectedNpcTypeId): void
    {
        // Arrange
        $wowheadService = app(WowheadService::class);
        $json           = sprintf('{"classification":1,"name":"A","type":%d}', $wowheadType);

        // Act
        $result = $wowheadService->getNpcData(210271, self::npcPageHtml(210271, $json));

        // Assert
        $this->assertSame($expectedNpcTypeId, $result?->getNpcTypeId());
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function npcCreatureTypeProvider(): array
    {
        return [
            'beast'      => [1, NpcType::BEAST],
            'undead'     => [6, NpcType::UNDEAD],
            'humanoid'   => [7, NpcType::HUMANOID],
            'aberration' => [15, NpcType::ABERRATION],
            'unknown'    => [13, NpcType::UNCATEGORIZED],
        ];
    }

    #[Test]
    public function getNpcData_givenPageForAnotherNpc_returnsNull(): void
    {
        // Arrange
        $html           = self::npcPageHtml(210269, '{"classification":1,"name":"Hired Muscle","type":7}');
        $wowheadService = app(WowheadService::class);

        // Act
        $result = $wowheadService->getNpcData(999999, $html);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function getNpcData_givenNpcDataWithEmptyName_returnsNull(): void
    {
        // Arrange
        $html           = self::npcPageHtml(210269, '{"classification":1,"name":"","type":7}');
        $wowheadService = app(WowheadService::class);

        // Act
        $result = $wowheadService->getNpcData(210269, $html);

        // Assert
        $this->assertNull($result);
    }

    /**
     * The two lines of a real Wowhead NPC page that carry its data, around some unrelated markup.
     */
    private static function npcPageHtml(int $npcId, string $npcJson): string
    {
        return implode(PHP_EOL, [
            '<html><head><meta property="og:title" content="Hired Muscle"></head><body>',
            sprintf('WH.Gatherer.addData(1, 1, {"%d":{"name_enus":"Hired Muscle","model":118999}});', $npcId),
            sprintf('$.extend(g_npcs[%d], %s);', $npcId, $npcJson),
            '    linksButton.dataset.displayId = 118999;',
            '</body></html>',
        ]);
    }
}
