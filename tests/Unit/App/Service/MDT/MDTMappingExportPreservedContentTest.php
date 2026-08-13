<?php

namespace Tests\Unit\App\Service\MDT;

use App\Service\MDT\Lua\LuaTableParser;
use App\Service\MDT\MDTMappingExportPreservedContent;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('MDT')]
final class MDTMappingExportPreservedContentTest extends TestCase
{
    #[Test]
    public function getSectionOrder_givenAFileWithItsOwnOrder_returnsThatOrder(): void
    {
        // Arrange - MDT does not order its sections the same way in every file
        $preservedContent = $this->getPreservedContent();

        // Act
        $sectionOrder = $preservedContent->getSectionOrder();

        // Assert
        $this->assertSame(
            ['dungeonList', 'mapInfo', 'dungeonTotalCount', 'dungeonMaps', 'dungeonSubLevels', 'mapPOIs', 'dungeonEnemies'],
            $sectionOrder,
        );
    }

    #[Test]
    public function getMapInfoValue_givenAValueWeCannotProduce_returnsIt(): void
    {
        // Arrange
        $preservedContent = $this->getPreservedContent();

        // Act & Assert
        $this->assertSame('1286831', (string)$preservedContent->getMapInfoValue('teleportId'));
        $this->assertSame('2011123', (string)$preservedContent->getMapInfoValue('iconId'));
        $this->assertNull($preservedContent->getMapInfoValue('doesNotExist'));
    }

    #[Test]
    public function getDungeonListValue_givenAHandWrittenTranslationKey_returnsItVerbatim(): void
    {
        // Arrange - regenerating this would turn L["Kings' Rest"] into L["KingsRest"], which MDT has no locale for
        $preservedContent = $this->getPreservedContent();

        // Act & Assert
        $this->assertSame('L["Kings\' Rest"]', (string)$preservedContent->getDungeonListValue());
        $this->assertSame('L["Kings\' Rest Sublevel"]', (string)$preservedContent->getSubLevelValue(1));
    }

    #[Test]
    public function getEnemyValue_givenAnNpcMDTCurates_returnsItsValues(): void
    {
        // Arrange
        $preservedContent = $this->getPreservedContent();

        // Act
        $spells = $preservedContent->getEnemyValue(134600, 'spells');

        // Assert
        $this->assertIsArray($spells);
        $this->assertSame([1234, 5678], array_keys($spells));
        $this->assertSame('true', (string)$preservedContent->getEnemyValue(134600, 'stealth'));
    }

    #[Test]
    public function getEnemyValue_givenAnNpcMDTDoesNotKnow_returnsNull(): void
    {
        // Arrange
        $preservedContent = $this->getPreservedContent();

        // Act & Assert
        $this->assertNull($preservedContent->getEnemyValue(999999, 'spells'));
        $this->assertNull($preservedContent->getEnemyValue(134600, 'doesNotExist'));
    }

    #[Test]
    public function getEnemyIndex_givenAKnownNpc_returnsMDTsOwnIndex(): void
    {
        // Arrange - keeping MDT's order is what stops one new NPC from shifting the whole file
        $preservedContent = $this->getPreservedContent();

        // Act & Assert
        $this->assertSame(1, $preservedContent->getEnemyIndex(134600));
        $this->assertSame(2, $preservedContent->getEnemyIndex(134616));
        $this->assertNull($preservedContent->getEnemyIndex(999999));
    }

    #[Test]
    public function getMatchingClone_givenACoordinateWithinTolerance_returnsMDTsClone(): void
    {
        // Arrange - lat/lng is double(8,2), so a round trip cannot reproduce MDT's ~14 digits
        $preservedContent = $this->getPreservedContent();

        // Act
        $clone = $preservedContent->getMatchingClone(134600, 589.12, -277.6);

        // Assert
        $this->assertIsArray($clone);
        $this->assertSame('589.10950032871', (string)$clone['x']);
        $this->assertSame('-277.61103164926', (string)$clone['y']);
    }

    #[Test]
    public function getMatchingClone_givenAnEnemyThatActuallyMoved_returnsNull(): void
    {
        // Arrange
        $preservedContent = $this->getPreservedContent();

        // Act & Assert - 9 units away is a move, not precision loss
        $this->assertNull($preservedContent->getMatchingClone(134600, 589.1, -268.7));
        $this->assertNull($preservedContent->getMatchingClone(999999, 589.1, -277.6));
    }

    #[Test]
    public function getMapPOIs_givenAnEmptyTable_returnsAnEmptyArrayRatherThanNull(): void
    {
        // Arrange - `= {};` means MDT deliberately has no POIs, which is not the same as having no table
        $preservedContent = MDTMappingExportPreservedContent::fromParsedAssignments(
            new LuaTableParser('MDT.mapPOIs[dungeonIndex] = {};')->parseDungeonIndexAssignments(),
        );

        // Act & Assert
        $this->assertSame([], $preservedContent->getMapPOIs());
        $this->assertNull(MDTMappingExportPreservedContent::fromParsedAssignments([])->getMapPOIs());
    }

    private function getPreservedContent(): MDTMappingExportPreservedContent
    {
        $source = <<<'LUA'
            MDT.dungeonList[dungeonIndex] = L["Kings' Rest"]
            MDT.mapInfo[dungeonIndex] = {
              teleportId = 1286831,
              iconId = 2011123,
              shortName = L["kingsRestShortName"],
              englishName = "King's Rest",
              mapID = 249
            };

            MDT.dungeonTotalCount[dungeonIndex] = { normal = 608 }

            MDT.dungeonMaps[dungeonIndex] = {
              [0] = "",
            }

            MDT.dungeonSubLevels[dungeonIndex] = {
              [1] = L["Kings' Rest Sublevel"],
            }

            MDT.mapPOIs[dungeonIndex] = {};

            MDT.dungeonEnemies[dungeonIndex] = {
              [1] = {
                ["name"] = "Sandswept Hunter",
                ["id"] = 134600,
                ["stealth"] = true,
                ["spells"] = {
                  [1234] = {
                    ["interruptible"] = true,
                  },
                  [5678] = {
                  },
                },
                ["clones"] = {
                  [1] = {
                    ["x"] = 589.10950032871,
                    ["y"] = -277.61103164926,
                    ["sublevel"] = 1,
                  },
                },
              },
              [2] = {
                ["name"] = "Barbed Krolusk",
                ["id"] = 134616,
                ["clones"] = {
                  [1] = {
                    ["x"] = 100.5,
                    ["y"] = -200.5,
                    ["sublevel"] = 1,
                  },
                },
              },
            };
            LUA;

        return MDTMappingExportPreservedContent::fromParsedAssignments(
            new LuaTableParser($source)->parseDungeonIndexAssignments(),
        );
    }
}
