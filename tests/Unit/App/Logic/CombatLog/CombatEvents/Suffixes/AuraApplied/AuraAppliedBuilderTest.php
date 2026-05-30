<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;

use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22\AuraAppliedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22_1\AuraAppliedV22_1;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class AuraAppliedBuilderTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('AuraApplied')]
    public function create_givenClassicVersion_returnsAuraAppliedV22(): void
    {
        // Act
        $result = AuraAppliedBuilder::create(CombatLogVersion::CLASSIC);

        // Assert
        Assert::assertInstanceOf(AuraAppliedV22::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('AuraApplied')]
    public function create_givenRetail11_1_7Version_returnsAuraAppliedV22(): void
    {
        // Act
        $result = AuraAppliedBuilder::create(CombatLogVersion::RETAIL_11_1_7);

        // Assert
        Assert::assertInstanceOf(AuraAppliedV22::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('AuraApplied')]
    public function create_givenRetail12_0_1Version_returnsAuraAppliedV22_1(): void
    {
        // Act
        $result = AuraAppliedBuilder::create(CombatLogVersion::RETAIL_12_0_1);

        // Assert
        Assert::assertInstanceOf(AuraAppliedV22_1::class, $result);
    }

    #[Test]
    #[Group('CombatLog')]
    #[Group('AuraApplied')]
    public function create_givenRetail12_0_5Version_returnsAuraAppliedV22_1(): void
    {
        // Act
        $result = AuraAppliedBuilder::create(CombatLogVersion::RETAIL_12_0_5);

        // Assert
        Assert::assertInstanceOf(AuraAppliedV22_1::class, $result);
    }
}
