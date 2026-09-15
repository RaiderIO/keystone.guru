<?php

namespace Tests\Unit\App\Service\MDT\Lua;

use App\Service\MDT\Exceptions\LuaParseException;
use App\Service\MDT\Lua\LuaLiteral;
use App\Service\MDT\Lua\LuaTableParser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('MDT')]
final class LuaTableParserTest extends TestCase
{
    #[Test]
    public function parseDungeonIndexAssignments_givenAnMDTDungeonFile_returnsEveryAssignment(): void
    {
        // Arrange
        $source = $this->getDungeonFile();

        // Act
        $assignments = new LuaTableParser($source)->parseDungeonIndexAssignments();

        // Assert
        $this->assertSame(
            ['dungeonList', 'mapInfo', 'dungeonMaps', 'dungeonSubLevels', 'dungeonTotalCount', 'mapPOIs', 'dungeonEnemies'],
            array_keys($assignments),
        );
    }

    #[Test]
    public function parseDungeonIndexAssignments_givenBareAndBracketedKeys_parsesBoth(): void
    {
        // Arrange
        $source = $this->getDungeonFile();

        // Act
        $assignments = new LuaTableParser($source)->parseDungeonIndexAssignments();

        // Assert - `teleportId = 1` is a bare key, `["name"] = ".."` a bracketed one
        $this->assertSame('1286812', (string)$assignments['mapInfo']['teleportId']);
        $this->assertSame('L["AltarOfFangsShortName"]', (string)$assignments['mapInfo']['shortName']);
        $this->assertSame('"Ritual Chieftain"', (string)$assignments['dungeonEnemies'][1]['name']);
    }

    #[Test]
    public function parseDungeonIndexAssignments_givenFullPrecisionCoordinates_keepsTheirExactSource(): void
    {
        // Arrange
        $source = $this->getDungeonFile();

        // Act
        $assignments = new LuaTableParser($source)->parseDungeonIndexAssignments();

        // Assert - a float cast would lose digits, which is the whole reason values stay literals
        $clone = $assignments['dungeonEnemies'][1]['clones'][1];
        $this->assertSame('59.443213885137', (string)$clone['x']);
        $this->assertSame('-205.55356532802', (string)$clone['y']);
    }

    #[Test]
    public function parseDungeonIndexAssignments_givenNumericTableKeys_keepsThemAsIntegers(): void
    {
        // Arrange
        $source = $this->getDungeonFile();

        // Act
        $assignments = new LuaTableParser($source)->parseDungeonIndexAssignments();

        // Assert - spell ids are keys, and MDT's clone indices carry gaps that must survive
        $this->assertSame([1221063, 1306517], array_keys($assignments['dungeonEnemies'][1]['spells']));
        $this->assertSame([1, 3], array_keys($assignments['dungeonEnemies'][2]['clones']));
    }

    #[Test]
    public function parseDungeonIndexAssignments_givenAQuotedStringHoldingBraces_doesNotTreatThemAsSyntax(): void
    {
        // Arrange
        $source = 'MDT.dungeonEnemies[dungeonIndex] = {
  [1] = {
    ["name"] = "A },{ name",
  },
};';

        // Act
        $assignments = new LuaTableParser($source)->parseDungeonIndexAssignments();

        // Assert
        $this->assertSame('A },{ name', $assignments['dungeonEnemies'][1]['name']->toUnquotedString());
    }

    #[Test]
    public function parseDungeonIndexAssignments_givenAnUnterminatedTable_throws(): void
    {
        // Arrange
        $source = 'MDT.dungeonEnemies[dungeonIndex] = {
  [1] = {
    ["name"] = "Never closed",
';

        // Assert
        $this->expectException(LuaParseException::class);

        // Act
        new LuaTableParser($source)->parseDungeonIndexAssignments();
    }

    #[Test]
    public function parseDungeonIndexAssignments_givenNoAssignments_returnsNothing(): void
    {
        // Arrange
        $source = 'local _, MDT = ...';

        // Act
        $assignments = new LuaTableParser($source)->parseDungeonIndexAssignments();

        // Assert
        $this->assertSame([], $assignments);
    }

    #[Test]
    public function parseDungeonIndexAssignments_givenAConcatenatedExpression_keepsItVerbatim(): void
    {
        // Arrange
        $source = $this->getDungeonFile();

        // Act
        $assignments = new LuaTableParser($source)->parseDungeonIndexAssignments();

        // Assert
        $customTextures = $assignments['dungeonMaps'][1]['customTextures'];
        $this->assertInstanceOf(LuaLiteral::class, $customTextures);
        $this->assertSame(
            '\'Interface\\\\AddOns\\\\\'..addonName..\'\\\\Midnight\\\\Textures\\\\AltarOfFangs\'',
            $customTextures->getLiteral(),
        );
    }

    /**
     * A cut down but structurally faithful copy of an MDT dungeon file.
     */
    private function getDungeonFile(): string
    {
        return <<<'LUA'
            local _, MDT = ...
            local addonName = MDT.AddonName
            local L = MDT.L
            local dungeonIndex = 164
            MDT.dungeonList[dungeonIndex] = L["AltarOfFangs"]
            MDT.mapInfo[dungeonIndex] = {
              teleportId = 1286812,
              shortName = L["AltarOfFangsShortName"],
              englishName = "Altar of Fangs",
              mapID = 588
            };

            local zones = { 2588 }
            for _, zone in ipairs(zones) do
              MDT.zoneIdToDungeonIdx[zone] = dungeonIndex
            end

            MDT.dungeonMaps[dungeonIndex] = {
              [0] = "",
              [1] = { customTextures = 'Interface\\AddOns\\'..addonName..'\\Midnight\\Textures\\AltarOfFangs' },
            }

            MDT.dungeonSubLevels[dungeonIndex] = {
              [1] = L["AltarOfFangs"],
            }

            MDT.dungeonTotalCount[dungeonIndex] = { normal = 817 }

            MDT.mapPOIs[dungeonIndex] = {
              [1] = {
                [1] = {
                  ["type"] = "dungeonEntrance",
                  ["x"] = 412.12345678901,
                  ["y"] = -111.98765432109,
                  ["sizeMult"] = 1.5,
                },
              },
            };

            MDT.dungeonEnemies[dungeonIndex] = {
              [1] = {
                ["name"] = "Ritual Chieftain",
                ["id"] = 270306,
                ["count"] = 25,
                ["spells"] = {
                  [1221063] = {
                    ["interruptible"] = true,
                  },
                  [1306517] = {
                  },
                },
                ["clones"] = {
                  [1] = {
                    ["x"] = 59.443213885137,
                    ["y"] = -205.55356532802,
                    ["g"] = 5,
                    ["sublevel"] = 1,
                  },
                },
              },
              [2] = {
                ["name"] = "Ritual Zealot",
                ["id"] = 270307,
                ["count"] = 5,
                ["clones"] = {
                  [1] = {
                    ["x"] = 100.5,
                    ["y"] = -200.5,
                    ["sublevel"] = 1,
                  },
                  [3] = {
                    ["x"] = 110.5,
                    ["y"] = -210.5,
                    ["sublevel"] = 1,
                  },
                },
              },
            };
            LUA;
    }
}
