<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\Energize;

use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\EnergizeBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V22\EnergizeV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Energize\V9\EnergizeV9;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class EnergizeBuilderTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffixes')]
    #[Group('Energize')]
    public function create_GivenClassicTbcVersion_ShouldReturnV9(): void
    {
        // Act
        $result = EnergizeBuilder::create(CombatLogVersion::CLASSIC_TBC_2_5_5);

        // Assert
        Assert::assertInstanceOf(EnergizeV9::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffixes')]
    #[Group('Energize')]
    public function create_GivenClassicSod1157Version_ShouldReturnV9(): void
    {
        // Act
        $result = EnergizeBuilder::create(CombatLogVersion::CLASSIC_SOD_1_15_7);

        // Assert
        Assert::assertInstanceOf(EnergizeV9::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffixes')]
    #[Group('Energize')]
    public function create_GivenRetailVersion_ShouldReturnV22(): void
    {
        // Act
        $result = EnergizeBuilder::create(CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(EnergizeV22::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffixes')]
    #[Group('Energize')]
    public function setParameters_GivenV9_ShouldParseCorrectly(): void
    {
        // Arrange
        $instance = new EnergizeV9(CombatLogVersion::CLASSIC_TBC_2_5_5);
        // Parameters: amount, powerType, [overEnergize, maxPower]
        $parameters = [100, 0, 20, 1000];

        // Act
        $instance->setParameters($parameters);

        // Assert
        Assert::assertEquals(100, $instance->getAmount());
        Assert::assertEquals(0, $instance->getPowerType());
        Assert::assertEquals(20, $instance->getOverEnergize());
        Assert::assertEquals(1000, $instance->getMaxPower());
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffixes')]
    #[Group('Energize')]
    public function setParameters_GivenV22_ShouldParseCorrectly(): void
    {
        // Arrange
        $instance = new EnergizeV22(CombatLogVersion::RETAIL_12_0_1);
        // Parameters: amount, overEnergize, powerType, [maxPower]
        $parameters = [100, 20, 0, 1000];

        // Act
        $instance->setParameters($parameters);

        // Assert
        Assert::assertEquals(100, $instance->getAmount());
        Assert::assertEquals(20, $instance->getOverEnergize());
        Assert::assertEquals(0, $instance->getPowerType());
        Assert::assertEquals(1000, $instance->getMaxPower());
    }
}
