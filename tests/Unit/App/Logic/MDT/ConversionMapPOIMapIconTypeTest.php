<?php

namespace Tests\Unit\App\Logic\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Entity\MDTMapPOI;
use App\Models\MapIconType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('MDT')]
final class ConversionMapPOIMapIconTypeTest extends TestCase
{
    #[Test]
    public function convertMDTMapPOIToMapIconTypeKey_givenMappedType_returnsItsMapIconTypeKey(): void
    {
        // Arrange
        $mdtMapPOI = new MDTMapPOI(1, ['type' => 'graveyard', 'x' => 1.0, 'y' => 2.0]);

        // Act
        $mapIconTypeKey = Conversion::convertMDTMapPOIToMapIconTypeKey($mdtMapPOI);

        // Assert
        $this->assertSame(MapIconType::MAP_ICON_TYPE_GRAVEYARD, $mapIconTypeKey);
    }

    #[Test]
    #[DataProvider('genericItemSpellIdProvider')]
    public function convertMDTMapPOIToMapIconTypeKey_givenGenericItemWithKnownSpellId_returnsItsOwnMapIconTypeKey(
        int    $spellId,
        string $expectedMapIconTypeKey,
    ): void {
        // Arrange
        $mdtMapPOI = new MDTMapPOI(1, [
            'type' => 'genericItem',
            'x'    => 1.0,
            'y'    => 2.0,
            'info' => ['spellId' => $spellId, 'texture' => 1003586, 'size' => 10],
        ]);

        // Act
        $mapIconTypeKey = Conversion::convertMDTMapPOIToMapIconTypeKey($mdtMapPOI);

        // Assert
        $this->assertSame($expectedMapIconTypeKey, $mapIconTypeKey);
        $this->assertFalse(Conversion::isMDTMapPOIUnhandled($mdtMapPOI));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function genericItemSpellIdProvider(): array
    {
        return [
            'Fel Contraband'     => [1270638, MapIconType::MAP_ICON_TYPE_MURDER_ROW_FEL_CONTRABAND],
            'Overload Golem'     => [1223133, MapIconType::MAP_ICON_TYPE_MURDER_ROW_OVERLOAD_GOLEM],
            'Felwyrm Egg'        => [1223570, MapIconType::MAP_ICON_TYPE_MURDER_ROW_FELWYRM_EGG],
            'Arcane Empowerment' => [1254550, MapIconType::MAP_ICON_TYPE_MAGISTERS_TERRACE_ARCANE_EMPOWERMENT],
            'Void Infusion'      => [244300, MapIconType::MAP_ICON_TYPE_SEAT_OF_THE_TRIUMVIRATE_VOID_INFUSION],
        ];
    }

    #[Test]
    public function convertMDTMapPOIToMapIconTypeKey_givenGenericItemWithUnknownSpellId_returnsNull(): void
    {
        // Arrange
        $mdtMapPOI = new MDTMapPOI(1, [
            'type' => 'genericItem',
            'x'    => 1.0,
            'y'    => 2.0,
            'info' => ['spellId' => 1, 'texture' => 2],
        ]);

        // Act
        $mapIconTypeKey = Conversion::convertMDTMapPOIToMapIconTypeKey($mdtMapPOI);

        // Assert
        $this->assertNull($mapIconTypeKey);
    }

    #[Test]
    public function isMDTMapPOIUnhandled_givenGenericItemWithUnknownSpellId_returnsTrue(): void
    {
        // Arrange - the exact shape that silently disappeared before #3993
        $mdtMapPOI = new MDTMapPOI(1, [
            'type' => 'genericItem',
            'x'    => 1.0,
            'y'    => 2.0,
            'info' => ['spellId' => 1, 'texture' => 2],
        ]);

        // Act
        $unhandled = Conversion::isMDTMapPOIUnhandled($mdtMapPOI);

        // Assert
        $this->assertTrue($unhandled);
        $this->assertSame(1, $mdtMapPOI->getSpellId());
        $this->assertSame(2, $mdtMapPOI->getTextureFileDataId());
    }

    #[Test]
    public function isMDTMapPOIUnhandled_givenMapLink_returnsFalse(): void
    {
        // Arrange - map links become dungeon floor switch markers instead of map icons
        $mdtMapPOI = new MDTMapPOI(1, ['type' => 'mapLink', 'x' => 1.0, 'y' => 2.0, 'target' => 2]);

        // Act & Assert
        $this->assertFalse(Conversion::isMDTMapPOIUnhandled($mdtMapPOI));
    }

    #[Test]
    #[DataProvider('notImportedTypeProvider')]
    public function isMDTMapPOIUnhandled_givenTypeThatIsPartOfMDTsOwnUI_returnsFalse(string $type): void
    {
        // Arrange
        $mdtMapPOI = new MDTMapPOI(1, ['type' => $type, 'x' => 1.0, 'y' => 2.0]);

        // Act & Assert
        $this->assertFalse(Conversion::isMDTMapPOIUnhandled($mdtMapPOI));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notImportedTypeProvider(): array
    {
        return [
            'zoom'      => ['zoom'],
            'textFrame' => ['textFrame'],
        ];
    }

    #[Test]
    public function mapIconTypeMappings_allReferenceAnExistingMapIconType(): void
    {
        // Arrange
        $mapIconTypeKeys = array_merge(
            array_values(Conversion::MAP_POI_TYPE_MAP_ICON_TYPE_MAPPING),
            array_values(Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING),
        );

        // Act & Assert - a typo here imports as the wrong icon, or fatals on MapIconType::ALL
        foreach ($mapIconTypeKeys as $mapIconTypeKey) {
            $this->assertArrayHasKey($mapIconTypeKey, MapIconType::ALL);
        }
    }

    #[Test]
    public function mapIconTypeMappings_allHaveATranslatedName(): void
    {
        // Arrange
        $mapIconTypeKeys = array_merge(
            array_values(Conversion::MAP_POI_TYPE_MAP_ICON_TYPE_MAPPING),
            array_values(Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING),
        );

        // Act & Assert - without this the icon renders with its raw key as its name
        foreach ($mapIconTypeKeys as $mapIconTypeKey) {
            $translationKey = sprintf('mapicontypes.%s', $mapIconTypeKey);

            $this->assertNotSame(
                $translationKey,
                __($translationKey),
                sprintf('Missing lang/en_US/mapicontypes.php entry for %s', $mapIconTypeKey),
            );
        }
    }
}
