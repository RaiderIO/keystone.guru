<?php

namespace Tests\Unit\App\Logic\MDT;

use App\Logic\MDT\Entity\MDTMapPOI;
use App\Logic\MDT\Entity\MDTMapPOITemplate;
use App\Logic\MDT\Entity\MDTMapPOIType;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MDTMapPOITest extends TestCase
{
    #[Test]
    public function construct_givenGenericAssignablePOIWithInfo_parsesAllFields(): void
    {
        // Arrange
        $raw = [
            'type'         => 'genericAssignablePOI',
            'x'            => 278.68213258199,
            'y'            => -197.71376774329,
            'index'        => 1,
            'textAnchor'   => 'RIGHT',
            'textAnchorTo' => 'LEFT',
            'info'         => [
                'name'        => 'Witherbark Prisoner',
                'atlas'       => 'QuestSkull',
                'size'        => 12,
                'fontSize'    => 4,
                'textOffsetX' => 4,
                'textOffsetY' => 0,
            ],
        ];

        // Act
        $poi = new MDTMapPOI(1, $raw);

        // Assert
        $this->assertSame(MDTMapPOIType::GenericAssignablePOI, $poi->getType());
        $this->assertSame(MDTMapPOITemplate::LinkPin, $poi->getTemplate());
        $this->assertSame(278.68213258199, $poi->getX());
        $this->assertSame(-197.71376774329, $poi->getY());
        $this->assertSame(1, $poi->getIndex());
        $this->assertSame('RIGHT', $poi->getTextAnchor());
        $this->assertSame('LEFT', $poi->getTextAnchorTo());
        $this->assertSame('QuestSkull', $poi->getSubType());
        $this->assertSame('Witherbark Prisoner', $poi->getInfo()['name']);
    }

    #[Test]
    public function construct_givenMinimalPOI_optionalFieldsAreNull(): void
    {
        // Arrange
        $raw = [
            'type' => 'graveyard',
            'x'    => 100.0,
            'y'    => 50.0,
        ];

        // Act
        $poi = new MDTMapPOI(1, $raw);

        // Assert
        $this->assertSame(MDTMapPOIType::Graveyard, $poi->getType());
        $this->assertNull($poi->getIndex());
        $this->assertNull($poi->getTextAnchor());
        $this->assertNull($poi->getTextAnchorTo());
        $this->assertNull($poi->getInfo());
        $this->assertNull($poi->getSubType());
        $this->assertNull($poi->getSizeMult());
    }

    #[Test]
    public function construct_givenUnknownType_throwsException(): void
    {
        // Arrange
        $raw = [
            'type' => 'unknownTypeThatDoesNotExist',
            'x'    => 0.0,
            'y'    => 0.0,
        ];

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Found new type/');

        // Act
        new MDTMapPOI(1, $raw);
    }

    #[Test]
    public function construct_givenDungeonEntrance_parsesSizeMultAndCoordinates(): void
    {
        // Arrange
        $raw = [
            'type'     => 'dungeonEntrance',
            'x'        => 67.867740272946,
            'y'        => -230.53987077699,
            'sizeMult' => 1.5,
        ];

        // Act
        $poi = new MDTMapPOI(1, $raw);

        // Assert
        $this->assertSame(MDTMapPOIType::DungeonEntrance, $poi->getType());
        $this->assertSame(67.867740272946, $poi->getX());
        $this->assertSame(-230.53987077699, $poi->getY());
        $this->assertSame(1.5, $poi->getSizeMult());
        $this->assertNull($poi->getInfo());
        $this->assertNull($poi->getSubType());
    }

    #[Test]
    public function construct_givenGenericItemWithTextureInfo_parsesInfoWithoutAtlas(): void
    {
        // Arrange
        $raw = [
            'type' => 'genericItem',
            'x'    => 174.3206078719,
            'y'    => -160.05458347394,
            'info' => [
                'texture' => 135740,
                'spellId' => 1254550,
                'size'    => 20,
            ],
        ];

        // Act
        $poi = new MDTMapPOI(1, $raw);

        // Assert
        $this->assertSame(MDTMapPOIType::GenericItem, $poi->getType());
        $this->assertSame(135740, $poi->getInfo()['texture']);
        $this->assertSame(1254550, $poi->getInfo()['spellId']);
        $this->assertSame(20, $poi->getInfo()['size']);
        $this->assertNull($poi->getSubType());
        $this->assertNull($poi->getSizeMult());
    }

    #[Test]
    public function construct_givenMapLinkWithTemplateAndTarget_parsesAllFields(): void
    {
        // Arrange
        $raw = [
            'template'  => 'MapLinkPinTemplate',
            'type'      => 'mapLink',
            'x'         => 389.24300443225,
            'y'         => -139.15934242295,
            'target'    => 1,
            'direction' => 1,
        ];

        // Act
        $poi = new MDTMapPOI(1, $raw);

        // Assert
        $this->assertSame(MDTMapPOIType::MapLink, $poi->getType());
        $this->assertSame(MDTMapPOITemplate::LinkPin, $poi->getTemplate());
        $this->assertSame(1, $poi->getTarget());
        $this->assertSame(1, $poi->getDirection());
        $this->assertNull($poi->getInfo());
        $this->assertNull($poi->getSizeMult());
    }

    #[Test]
    public function construct_givenMinimalPOI_sizeMultIsNull(): void
    {
        // Arrange
        $raw = [
            'type' => 'graveyard',
            'x'    => 100.0,
            'y'    => 50.0,
        ];

        // Act
        $poi = new MDTMapPOI(1, $raw);

        // Assert
        $this->assertNull($poi->getSizeMult());
    }

    #[Test]
    public function toArray_givenGenericAssignablePOIWithInfo_includesAllFields(): void
    {
        // Arrange
        $raw = [
            'type'         => 'genericAssignablePOI',
            'x'            => 278.68213258199,
            'y'            => -197.71376774329,
            'index'        => 1,
            'textAnchor'   => 'RIGHT',
            'textAnchorTo' => 'LEFT',
            'info'         => [
                'name'        => 'Witherbark Prisoner',
                'atlas'       => 'QuestSkull',
                'size'        => 12,
                'fontSize'    => 4,
                'textOffsetX' => 4,
                'textOffsetY' => 0,
            ],
        ];

        // Act
        $result = new MDTMapPOI(1, $raw)->toArray();

        // Assert
        $this->assertSame('genericAssignablePOI', $result['type']);
        $this->assertSame(1, $result['index']);
        $this->assertSame('RIGHT', $result['textAnchor']);
        $this->assertSame('LEFT', $result['textAnchorTo']);
        $this->assertSame($raw['info'], $result['info']);
    }
}
