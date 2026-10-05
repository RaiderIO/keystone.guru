<?php

namespace Tests\Unit\App\Logic\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Floor\Floor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ConversionTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    #[DataProvider('checkGetExpansionName_GivenDungeonKey_ShouldBeCorrect_Provider')]
    #[Group('MDT')]
    public function checkGetExpansionName_GivenDungeonKey_ShouldBeCorrect(string $dungeonKey, string $expectedExpansionKey): void
    {
        // Test
        $expansionKey = Conversion::getExpansionName($dungeonKey);

        // Assert
        $this->assertEquals($expectedExpansionKey, $expansionKey);
    }

    /**
     * @return array<int, mixed>
     */
    public static function checkGetExpansionName_GivenDungeonKey_ShouldBeCorrect_Provider(): array
    {
        $expansions = [
            Expansion::EXPANSION_CATACLYSM,
            Expansion::EXPANSION_MOP,
            Expansion::EXPANSION_LEGION,
            Expansion::EXPANSION_BFA,
            Expansion::EXPANSION_SHADOWLANDS,
            Expansion::EXPANSION_DRAGONFLIGHT,
            // Midnight must be covered: MDT 6.2 moved King's Rest, Temple of Sethraliss and Ruby Life
            // Pools out of MDT_Legacy into the mainline Midnight/ folder, so their BFA/Dragonflight
            // entries are commented out and the Midnight block is the only thing that resolves them.
            Expansion::EXPANSION_MIDNIGHT,
        ];

        $result = [];
        $seen   = [];
        // A dungeon key may deliberately appear under more than one expansion (Algeth'ar Academy is
        // listed under both Dragonflight and Midnight). getExpansionName() is an array_find_key over
        // DUNGEON_NAME_MAPPING, so the FIRST declaring block wins - assert against that block only,
        // walking the constant in declaration order rather than the order of $expansions above.
        foreach (Conversion::DUNGEON_NAME_MAPPING as $expansion => $dungeons) {
            foreach ($dungeons as $dungeonKey => $value) {
                // Record every key, including ones from expansions this test does not assert on, so a
                // key first declared outside $expansions is never attributed to a later block.
                if (isset($seen[$dungeonKey])) {
                    continue;
                }

                $seen[$dungeonKey] = true;

                if (in_array($expansion, $expansions, true)) {
                    $result[] = [$dungeonKey, $expansion];
                }
            }
        }

        return $result;
    }

    /**
     * Every mapped dungeon must have a lua file where DUNGEON_NAME_MAPPING says it does, in whichever
     * package MAINLINE_MDT_DUNGEONS routes it to. Mirrors the path MDTDungeon::getLua() builds - which
     * throws "Unable to find file" at runtime when the mapping and the vendor tree disagree.
     *
     * This is the check that catches an MDT release moving or deleting a dungeon's lua, and equally a
     * dungeon that was needlessly unmapped while its file is still shipped somewhere else.
     */
    #[Test]
    #[DataProvider('mdtDungeonName_givenEveryMappedDungeon_resolvesToAnExistingLuaFile_Provider')]
    #[Group('MDT')]
    public function mdtDungeonName_givenEveryMappedDungeon_resolvesToAnExistingLuaFile(string $dungeonKey): void
    {
        // Arrange
        $mdtExpansionName = Conversion::getMDTExpansionName($dungeonKey);
        $mdtDungeonName   = Conversion::getMDTDungeonName($dungeonKey);

        $this->assertNotNull($mdtExpansionName, sprintf('No MDT expansion folder for %s', $dungeonKey));
        $this->assertNotNull($mdtDungeonName, sprintf('No MDT dungeon name for %s', $dungeonKey));

        $package = Conversion::isDungeonInMainlineMDT(new Dungeon(['key' => $dungeonKey]))
            ? 'mythicdungeontools'
            : 'mdt-legacy';

        // Act
        $path = base_path(sprintf('vendor/nnoggie/%s/%s/%s.lua', $package, $mdtExpansionName, $mdtDungeonName));

        // Assert
        $this->assertFileExists($path, sprintf(
            '%s is mapped to %s/%s/%s.lua, but that file does not exist. Either the MDT package moved it '
            . '(update DUNGEON_NAME_MAPPING / MAINLINE_MDT_DUNGEONS) or the dungeon should be unmapped.',
            $dungeonKey,
            $package,
            $mdtExpansionName,
            $mdtDungeonName,
        ));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function mdtDungeonName_givenEveryMappedDungeon_resolvesToAnExistingLuaFile_Provider(): array
    {
        $result = [];

        foreach (Conversion::DUNGEON_NAME_MAPPING as $dungeons) {
            foreach (array_keys($dungeons) as $dungeonKey) {
                $result[$dungeonKey] ??= [$dungeonKey];
            }
        }

        return $result;
    }

    /**
     * A dungeon key listed in two live expansion blocks silently resolves to whichever is declared first,
     * so a half-finished move between MDT packages looks correct while pointing at the wrong package.
     * That is exactly how Algeth'ar Academy came to resolve to a Dragonflight lua that MDT had deleted.
     */
    #[Test]
    #[Group('MDT')]
    public function dungeonNameMapping_givenEveryDungeonKey_isDeclaredInExactlyOneExpansion(): void
    {
        // Arrange
        $expansionsByDungeonKey = [];
        foreach (Conversion::DUNGEON_NAME_MAPPING as $expansion => $dungeons) {
            foreach (array_keys($dungeons) as $dungeonKey) {
                $expansionsByDungeonKey[$dungeonKey][] = $expansion;
            }
        }

        $duplicates = [];
        foreach ($expansionsByDungeonKey as $dungeonKey => $expansions) {
            if (count($expansions) > 1) {
                $duplicates[] = sprintf('%s => %s', $dungeonKey, implode(', ', $expansions));
            }
        }

        // Assert
        $this->assertEmpty($duplicates, sprintf(
            "These dungeon keys are declared in more than one expansion block, so getExpansionName() "
            . "silently returns the first one:\n%s",
            implode("\n", $duplicates),
        ));
    }

    #[Test]
    #[Group('MDT')]
    public function getExpansionName_givenDungeonKeyMDTDoesNotKnow_returnsNull(): void
    {
        // Act
        $expansionName = Conversion::getExpansionName('not_an_mdt_dungeon');

        // Assert
        $this->assertNull($expansionName);
        $this->assertFalse(Conversion::hasMDTExpansionName('not_an_mdt_dungeon'));
        $this->assertFalse(Conversion::hasMDTDungeonName('not_an_mdt_dungeon'));
        $this->assertNull(Conversion::getMDTDungeonName('not_an_mdt_dungeon'));
    }

    #[Test]
    #[Group('MDT')]
    public function getMDTDungeonName_givenMappedDungeonKey_returnsMDTsFileName(): void
    {
        // Act
        $mdtDungeonName = Conversion::getMDTDungeonName('kingsrest');

        // Assert
        $this->assertSame(Conversion::DUNGEON_NAME_MAPPING[Conversion::getExpansionName('kingsrest')]['kingsrest'], $mdtDungeonName);
        $this->assertSame('KingsRest', $mdtDungeonName);
        $this->assertTrue(Conversion::hasMDTDungeonName('kingsrest'));
        $this->assertTrue(Conversion::hasMDTExpansionName('kingsrest'));
    }

    #[Test]
    #[Group('MDT')]
    public function convertMDTCoordinateToLatLng_givenMDTCoordinate_returnsScaledLatLngWithAxesSwapped(): void
    {
        // Arrange - MDT's y is our lat and its x our lng, both 2.185 times larger
        $floor = new Floor();

        // Act
        $latLng = Conversion::convertMDTCoordinateToLatLng(['x' => 10.123, 'y' => -218.5], $floor);

        // Assert
        $this->assertSame(-100.0, $latLng->getLat());
        $this->assertSame(4.63, $latLng->getLng());
        $this->assertSame($floor, $latLng->getFloor());
    }

    #[Test]
    #[Group('MDT')]
    public function convertLatLngToMDTCoordinate_givenLatLng_returnsScaledCoordinateRoundedToOneDecimal(): void
    {
        // Arrange
        $latLng = new LatLng(-100.0, 200.04);

        // Act
        $mdtCoordinate          = Conversion::convertLatLngToMDTCoordinate($latLng);
        $mdtCoordinateString    = Conversion::convertLatLngToMDTCoordinateString($latLng);
        $mdtCoordinateUnrounded = Conversion::convertLatLngToMDTCoordinateUnrounded($latLng);

        // Assert
        $this->assertSame(['y' => -218.5, 'x' => 437.1], $mdtCoordinate);
        $this->assertSame(['y' => '-218.5', 'x' => '437.1'], $mdtCoordinateString);
        $this->assertEqualsWithDelta(437.0874, $mdtCoordinateUnrounded['x'], 0.00001);
        $this->assertEqualsWithDelta(-218.5, $mdtCoordinateUnrounded['y'], 0.00001);
    }
}
