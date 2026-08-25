<?php

namespace Tests\Unit\App\Logic\CombatLog\Guid;

use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\MissType\Absorb;
use App\Logic\CombatLog\Guid\MissType\Miss;
use App\Logic\CombatLog\Guid\Player;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class GuidTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    public function createFromGuidString_givenZeroGuid_returnsNull(): void
    {
        // Act
        $result = Guid::createFromGuidString('0000000000000000');

        // Assert
        Assert::assertNull($result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    public function createFromGuidString_givenPlayerGuid_returnsPlayerInstance(): void
    {
        // Arrange
        $guidString = 'Player-580-0AE12FF4';

        // Act
        $result = Guid::createFromGuidString($guidString);

        // Assert
        Assert::assertInstanceOf(Player::class, $result);
        Assert::assertEquals($guidString, $result->getGuid());
        Assert::assertEquals($guidString, (string)$result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    public function createFromGuidString_givenCreatureGuid_returnsCreatureInstanceWithCorrectFields(): void
    {
        // Arrange
        $guidString = 'Creature-0-4241-2526-8814-197219-000043ACE6';

        // Act
        $result = Guid::createFromGuidString($guidString);

        // Assert
        Assert::assertInstanceOf(Creature::class, $result);
        Assert::assertEquals($guidString, $result->getGuid());
        Assert::assertEquals(Creature::CREATURE_UNIT_TYPE_CREATURE, $result->getUnitType());
        Assert::assertEquals(0, $result->getUnknown1());
        Assert::assertEquals(4241, $result->getServerId());
        Assert::assertEquals(2526, $result->getInstanceId());
        Assert::assertEquals(8814, $result->getZoneUID());
        Assert::assertEquals(197219, $result->getId());
        Assert::assertEquals('000043ACE6', $result->getSpawnUID());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    public function createFromGuidString_givenAbsorbMissType_returnsAbsorbInstance(): void
    {
        // Act
        $result = Guid::createFromGuidString('ABSORB');

        // Assert
        Assert::assertInstanceOf(Absorb::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    public function createFromGuidString_givenMissMissType_returnsMissInstance(): void
    {
        // Act
        $result = Guid::createFromGuidString('MISS');

        // Assert
        Assert::assertInstanceOf(Miss::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    #[DataProvider('isCreatureGuidString_givenACreatureMappedPrefix_returnsTrue_DataProvider')]
    public function isCreatureGuidString_givenACreatureMappedPrefix_returnsTrue(string $guidString): void
    {
        // Act
        $result = Guid::isCreatureGuidString($guidString);

        // Assert
        Assert::assertTrue($result);
    }

    /**
     * @return array<string, mixed>
     */
    public static function isCreatureGuidString_givenACreatureMappedPrefix_returnsTrue_DataProvider(): array
    {
        return [
            'Creature'   => ['Creature-0-4241-2526-8814-197219-000043ACE6'],
            'GameObject' => ['GameObject-0-4241-2526-8814-197219-000043ACE6'],
            'Pet'        => ['Pet-0-4241-2526-8814-197219-000043ACE6'],
            'Vehicle'    => ['Vehicle-0-4241-2526-8814-197219-000043ACE6'],
        ];
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    #[DataProvider('isCreatureGuidString_givenANonCreatureMappedGuid_returnsFalse_DataProvider')]
    public function isCreatureGuidString_givenANonCreatureMappedGuid_returnsFalse(string $guidString): void
    {
        // Act
        $result = Guid::isCreatureGuidString($guidString);

        // Assert
        Assert::assertFalse($result);
    }

    /**
     * @return array<string, mixed>
     */
    public static function isCreatureGuidString_givenANonCreatureMappedGuid_returnsFalse_DataProvider(): array
    {
        return [
            'Player'         => ['Player-580-0AE12FF4'],
            'Item'           => ['Item-0-0-0-0-197219-000043ACE6'],
            'nil GUID'       => ['0000000000000000'],
            'MISS type'      => ['MISS'],
            'unknown prefix' => ['SomeUnknownPrefix-0-4241-2526-8814-197219-000043ACE6'],
            'empty string'   => [''],
        ];
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    public function isPlayerGuidString_givenAPlayerGuid_returnsTrue(): void
    {
        // Act
        $result = Guid::isPlayerGuidString('Player-580-0AE12FF4');

        // Assert
        Assert::assertTrue($result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Guid')]
    #[DataProvider('isPlayerGuidString_givenANonPlayerGuid_returnsFalse_DataProvider')]
    public function isPlayerGuidString_givenANonPlayerGuid_returnsFalse(string $guidString): void
    {
        // Act
        $result = Guid::isPlayerGuidString($guidString);

        // Assert
        Assert::assertFalse($result);
    }

    /**
     * @return array<string, mixed>
     */
    public static function isPlayerGuidString_givenANonPlayerGuid_returnsFalse_DataProvider(): array
    {
        return [
            'Creature'       => ['Creature-0-4241-2526-8814-197219-000043ACE6'],
            'GameObject'     => ['GameObject-0-4241-2526-8814-197219-000043ACE6'],
            'Item'           => ['Item-0-0-0-0-197219-000043ACE6'],
            'nil GUID'       => ['0000000000000000'],
            'MISS type'      => ['MISS'],
            'unknown prefix' => ['SomeUnknownPrefix-0-4241-2526-8814-197219-000043ACE6'],
            'empty string'   => [''],
        ];
    }
}
