<?php

namespace Tests\Unit\App\Logic\CombatLog\Guid;

use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Guid;
use App\Logic\CombatLog\Guid\MissType\Absorb;
use App\Logic\CombatLog\Guid\MissType\Miss;
use App\Logic\CombatLog\Guid\Player;
use PHPUnit\Framework\Assert;
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
}
