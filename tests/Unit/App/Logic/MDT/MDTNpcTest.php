<?php

namespace Tests\Unit\App\Logic\MDT;

use App\Logic\MDT\Entity\MDTNpc;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('MDT')]
final class MDTNpcTest extends TestCase
{
    #[Test]
    public function isBoss_givenIsBossTrue_returnsTrue(): void
    {
        // Arrange
        $raw           = $this->minimalRawMdtNpc();
        $raw['isBoss'] = true;

        // Act
        $npc = new MDTNpc(1, $raw);

        // Assert
        $this->assertTrue($npc->isBoss());
    }

    #[Test]
    public function isBoss_givenIsBossFalse_returnsFalse(): void
    {
        // Arrange
        $raw           = $this->minimalRawMdtNpc();
        $raw['isBoss'] = false;

        // Act
        $npc = new MDTNpc(1, $raw);

        // Assert
        $this->assertFalse($npc->isBoss());
    }

    #[Test]
    public function isBoss_givenIsBossNotSet_returnsFalse(): void
    {
        // Arrange
        $raw = $this->minimalRawMdtNpc();

        // Act
        $npc = new MDTNpc(1, $raw);

        // Assert
        $this->assertFalse($npc->isBoss());
    }

    #[Test]
    public function getClones_givenCloneWithoutSublevel_returnsItOnSublevelOne(): void
    {
        // Arrange
        $raw           = $this->minimalRawMdtNpc();
        $raw['clones'] = [
            1 => ['x' => 1.0, 'y' => 2.0],
            2 => ['x' => 3.0, 'y' => 4.0, 'sublevel' => 2],
        ];

        // Act
        $clones = new MDTNpc(1, $raw)->getClones();

        // Assert
        $this->assertSame(1, $clones[1]['sublevel'] ?? null);
        $this->assertSame(2, $clones[2]['sublevel']);
    }

    #[Test]
    public function getClones_givenClonesOutOfOrder_returnsThemSortedByIndex(): void
    {
        // Arrange - Lua hands tables over in no particular order
        $raw           = $this->minimalRawMdtNpc();
        $raw['clones'] = [
            3 => ['x' => 5.0, 'y' => 6.0, 'sublevel' => 1],
            1 => ['x' => 1.0, 'y' => 2.0, 'sublevel' => 1],
            2 => ['x' => 3.0, 'y' => 4.0, 'sublevel' => 1],
        ];

        // Act
        $clones = new MDTNpc(1, $raw)->getClones();

        // Assert
        $this->assertSame([1, 2, 3], array_keys($clones));
    }

    #[Test]
    public function getCountTeeming_givenNoTeemingCount_returnsMinusOne(): void
    {
        // Arrange
        $raw = $this->minimalRawMdtNpc();

        // Act
        $countTeeming = new MDTNpc(1, $raw)->getCountTeeming();

        // Assert
        $this->assertSame(-1, $countTeeming);
    }

    #[Test]
    public function getCountTeeming_givenTeemingCount_returnsIt(): void
    {
        // Arrange
        $raw                 = $this->minimalRawMdtNpc();
        $raw['teemingCount'] = 7;

        // Act
        $countTeeming = new MDTNpc(1, $raw)->getCountTeeming();

        // Assert
        $this->assertSame(7, $countTeeming);
    }

    #[Test]
    public function isValid_givenEmissary_returnsFalse(): void
    {
        // Arrange
        $raw       = $this->minimalRawMdtNpc();
        $raw['id'] = 155432;

        // Act
        $npc = new MDTNpc(1, $raw);

        // Assert
        $this->assertTrue($npc->isEmissary());
        $this->assertFalse($npc->isValid());
        $this->assertTrue(new MDTNpc(1, $this->minimalRawMdtNpc())->isValid());
    }

    #[Test]
    public function getName_givenNoName_returnsNull(): void
    {
        // Arrange
        $npc = new MDTNpc(1, $this->minimalRawMdtNpc());

        // Act
        $name = $npc->getName();

        // Assert
        $this->assertNull($name);
    }

    #[Test]
    public function getName_givenName_returnsIt(): void
    {
        // Arrange
        $raw         = $this->minimalRawMdtNpc();
        $raw['name'] = 'Sureki Webmage';
        $npc         = new MDTNpc(1, $raw);

        // Act
        $name = $npc->getName();

        // Assert
        $this->assertSame('Sureki Webmage', $name);
    }

    #[Test]
    public function getCreatureType_givenNoCreatureType_returnsNull(): void
    {
        // Arrange
        $npc = new MDTNpc(1, $this->minimalRawMdtNpc());

        // Act
        $creatureType = $npc->getCreatureType();

        // Assert
        $this->assertNull($creatureType);
    }

    #[Test]
    public function getCreatureType_givenCreatureType_returnsIt(): void
    {
        // Arrange
        $raw                 = $this->minimalRawMdtNpc();
        $raw['creatureType'] = 'Humanoid';
        $npc                 = new MDTNpc(1, $raw);

        // Act
        $creatureType = $npc->getCreatureType();

        // Assert
        $this->assertSame('Humanoid', $creatureType);
    }

    #[Test]
    public function toArray_givenNoNameOrCreatureType_returnsNullForBoth(): void
    {
        // Arrange
        $npc = new MDTNpc(1, $this->minimalRawMdtNpc());

        // Act
        $array = $npc->toArray();

        // Assert
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('creatureType', $array);
        $this->assertNull($array['name']);
        $this->assertNull($array['creatureType']);
        $this->assertSame(246404, $array['id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalRawMdtNpc(): array
    {
        return [
            'clones' => [],
            'id'     => 246404,
            'scale'  => 1.0,
            'health' => 21891970,
            'count'  => 0,
        ];
    }
}
