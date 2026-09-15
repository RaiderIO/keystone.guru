<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Service\MDT\MDTMappingExportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Pins the shape of the Lua we hand to MDT. Every assertion here corresponds to something MDT's own
 * dungeon files do, so a regression shows up as a diff in MDT's repository rather than in ours.
 *
 * Deliberately assertions rather than a golden file over a seeded dungeon: enemy positions, counts and
 * packs change with every mapping pass, so a whole file fixture would only ever be churn.
 */
#[Group('MDT')]
final class MDTMappingExportServiceFormatTest extends PublicTestCase
{
    #[Test]
    public function getMDTMappingAsLuaString_givenADungeon_writesMDTsMapInfoShape(): void
    {
        // Arrange & Act
        $lua = $this->getLuaString();

        // Assert - shortName is camel cased in MDT's files, englishName is a plain string and not a
        // translation key, and there is no trailing comma before the closing brace
        $this->assertStringContainsString('  shortName = L["MurderRowShortName"],', $lua);
        $this->assertStringContainsString('  englishName = "Murder Row",', $lua);
        $this->assertMatchesRegularExpression('/  mapID = \d+\n};/', $lua);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenADungeon_exportsTheChallengeModeIdAsMapID(): void
    {
        // Arrange
        $dungeon = $this->getDungeon();

        // Act
        $lua = $this->getLuaString();

        // Assert - MDT's mapID is the challenge mode id, not our map_id
        $this->assertStringContainsString(sprintf('  mapID = %d', $dungeon->challenge_mode_id), $lua);
        $this->assertStringNotContainsString(sprintf('  mapID = %d', $dungeon->map_id), $lua);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenADungeon_exportsTheTextureUnderMDTsExpansionFolder(): void
    {
        // Arrange & Act
        $lua = $this->getLuaString();

        // Assert - MDT ships Murder Row under Midnight, which is also our expansion for it, but King's Rest
        // is BFA to us and Midnight to MDT - the folder always follows MDT
        $this->assertStringContainsString('\\\\Midnight\\\\Textures\\\\MurderRow', $lua);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenADungeon_exportsTheTotalCountWithoutTeeming(): void
    {
        // Arrange & Act
        $lua = $this->getLuaString();

        // Assert - MDT dropped teeming entirely
        $this->assertMatchesRegularExpression('/MDT\.dungeonTotalCount\[dungeonIndex] = \{ normal = \d+ }\n/', $lua);
        $this->assertStringNotContainsString('teeming', $lua);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenADungeon_omitsFieldsMDTHasNoSchemaFor(): void
    {
        // Arrange & Act
        $lua = $this->getLuaString();

        // Assert - healthPercentage is in no MDT file and in no MDT schema
        $this->assertStringNotContainsString('healthPercentage', $lua);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenNoFileToPreserveFrom_exportsNoSpells(): void
    {
        // Arrange & Act
        $lua = $this->getLuaString();

        // Assert - our npc_spells are the NPC Compendium's data and are never handed to MDT
        $this->assertStringNotContainsString('["spells"]', $lua);
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenRegenerateMapPOIs_writesOurOwnDungeonEntrance(): void
    {
        // Arrange - this path never produced anything at all until floorsForMapFacade() was iterated
        // without get(), so it is worth pinning that it now emits something in MDT's shape
        $dungeon        = $this->getDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        // Act
        $lua = app(MDTMappingExportServiceInterface::class)->getMDTMappingAsLuaString(
            $mappingVersion,
            excludeTranslations: true,
            regenerateMapPOIs: true,
        );

        // Assert - x/y come straight after the type, and MDT sizes every dungeon entrance up by 1.5
        $this->assertMatchesRegularExpression(
            '/\["type"] = "dungeonEntrance",\n\s+\["x"] = [-\d.]+,\n\s+\["y"] = [-\d.]+,\n\s+\["sizeMult"] = 1\.5,/',
            $lua,
        );
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenADungeon_producesLuaWithoutTrailingWhitespace(): void
    {
        // Arrange & Act
        $lua = $this->getLuaString();

        // Assert
        foreach (explode("\n", $lua) as $lineNumber => $line) {
            $this->assertSame(rtrim($line), $line, sprintf('Line %d has trailing whitespace', $lineNumber + 1));
        }
    }

    #[Test]
    public function getMDTMappingAsLuaString_givenADungeon_endsTheEnemyTableTheWayMDTDoes(): void
    {
        // Arrange & Act
        $lua = $this->getLuaString();

        // Assert
        $this->assertStringEndsWith("};\n", $lua);
    }

    private function getDungeon(): Dungeon
    {
        return Dungeon::where('key', 'murder_row')->firstOrFail();
    }

    private function getLuaString(): string
    {
        $dungeon        = $this->getDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        return app(MDTMappingExportServiceInterface::class)
            ->getMDTMappingAsLuaString($mappingVersion, excludeTranslations: true);
    }
}
