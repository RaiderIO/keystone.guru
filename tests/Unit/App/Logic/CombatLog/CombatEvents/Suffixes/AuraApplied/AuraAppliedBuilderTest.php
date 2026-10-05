<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied;

use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\AuraAppliedBuilder;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22\AuraAppliedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\AuraApplied\V22_1\AuraAppliedV22_1;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[Test]
    #[Group('CombatLog')]
    #[Group('AuraApplied')]
    #[DataProvider('create_givenAVersionBefore12_returnsAuraAppliedV22_DataProvider')]
    public function create_givenAVersionBefore12_returnsAuraAppliedV22(int $combatLogVersion): void
    {
        // Act
        $result = AuraAppliedBuilder::create($combatLogVersion);

        // Assert
        Assert::assertSame(AuraAppliedV22::class, $result::class);
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function create_givenAVersionBefore12_returnsAuraAppliedV22_DataProvider(): array
    {
        return [
            'classic tbc 2.5.5'  => [CombatLogVersion::CLASSIC_TBC_2_5_5],
            'classic sod 1.15.5' => [CombatLogVersion::CLASSIC_SOD_1_15_5],
            'classic sod 1.15.6' => [CombatLogVersion::CLASSIC_SOD_1_15_6],
            'classic sod 1.15.7' => [CombatLogVersion::CLASSIC_SOD_1_15_7],
            'retail 10.1.0'      => [CombatLogVersion::RETAIL_10_1_0],
            'retail 11.0.2'      => [CombatLogVersion::RETAIL_11_0_2],
            'retail 11.0.5'      => [CombatLogVersion::RETAIL_11_0_5],
            'retail 11.0.7'      => [CombatLogVersion::RETAIL_11_0_7],
            'retail 11.1.0'      => [CombatLogVersion::RETAIL_11_1_0],
        ];
    }

    /**
     * @param array<int, int|string> $parameters
     */
    #[Test]
    #[Group('CombatLog')]
    #[Group('AuraApplied')]
    #[DataProvider('setParameters_givenAuraAppliedParameters_returnsEachField_DataProvider')]
    public function setParameters_givenAuraAppliedParameters_returnsEachField(
        int    $combatLogVersion,
        array  $parameters,
        string $expectedAuraType,
        ?int   $expectedAmount,
        ?int   $expectedUnknown,
    ): void {
        // Arrange
        /** @var AuraAppliedV22|AuraAppliedV22_1 $auraApplied */
        $auraApplied = AuraAppliedBuilder::create($combatLogVersion);

        // Act
        $auraApplied->setParameters($parameters);

        // Assert
        Assert::assertSame($expectedAuraType, $auraApplied->getAuraType());
        Assert::assertSame($expectedAmount, $auraApplied->getAmount());
        Assert::assertSame($expectedUnknown, $auraApplied->getUnknown());
    }

    /**
     * @return array<string, array{0: int, 1: array<int, int|string>, 2: string, 3: int|null, 4: int|null}>
     */
    public static function setParameters_givenAuraAppliedParameters_returnsEachField_DataProvider(): array
    {
        return [
            'v22 type only'                  => [CombatLogVersion::RETAIL_11_1_7, ['BUFF'], 'BUFF', null, null],
            'v22 type and amount'            => [CombatLogVersion::RETAIL_11_1_7, ['DEBUFF', 5], 'DEBUFF', 5, null],
            'v22.1 type only'                => [CombatLogVersion::RETAIL_12_0_5, ['BUFF'], 'BUFF', null, null],
            'v22.1 type, amount and unknown' => [CombatLogVersion::RETAIL_12_0_5, ['DEBUFF', 5, 7], 'DEBUFF', 5, 7],
        ];
    }
}
