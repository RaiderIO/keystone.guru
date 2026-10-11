<?php

namespace Tests\Unit\App\Logic\CombatLog;

use App\Logic\CombatLog\CombatLogLineage;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogVersion')]
final class CombatLogLineageTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('fromCombatLogVersion_givenACombatLogVersion_returnsItsLineage_DataProvider')]
    public function fromCombatLogVersion_givenACombatLogVersion_returnsItsLineage(
        int              $combatLogVersion,
        CombatLogLineage $expectedLineage,
    ): void {
        // Act
        $result = CombatLogLineage::fromCombatLogVersion($combatLogVersion);

        // Assert
        Assert::assertSame($expectedLineage, $result);
    }

    /**
     * @return array<string, array{0: int, 1: CombatLogLineage}>
     */
    public static function fromCombatLogVersion_givenACombatLogVersion_returnsItsLineage_DataProvider(): array
    {
        return [
            'classic'                 => [CombatLogVersion::CLASSIC, CombatLogLineage::Classic],
            'sod 1.15.5'              => [CombatLogVersion::CLASSIC_SOD_1_15_5, CombatLogLineage::Classic],
            'tbc 2.5.5'               => [CombatLogVersion::CLASSIC_TBC_2_5_5, CombatLogLineage::Classic],
            'unregistered classic'    => [9_005_005_000, CombatLogLineage::Classic],
            'retail 10.1.0'           => [CombatLogVersion::RETAIL_10_1_0, CombatLogLineage::Retail],
            'retail 12.1.0'           => [CombatLogVersion::RETAIL_12_1_0, CombatLogLineage::Retail],
            'unregistered old retail' => [19_009_002_007, CombatLogLineage::Retail],
        ];
    }

    #[Test]
    #[DataProvider('isSupported_givenACombatLogVersion_returnsWhetherItsLineageCanParseIt_DataProvider')]
    public function isSupported_givenACombatLogVersion_returnsWhetherItsLineageCanParseIt(
        int  $combatLogVersion,
        bool $expectedSupported,
    ): void {
        // Act
        $result = CombatLogVersion::isSupported($combatLogVersion);

        // Assert
        Assert::assertSame($expectedSupported, $result);
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function isSupported_givenACombatLogVersion_returnsWhetherItsLineageCanParseIt_DataProvider(): array
    {
        return [
            'oldest classic'              => [CombatLogVersion::CLASSIC, true],
            'unregistered classic'        => [9_005_005_000, true],
            'oldest retail'               => [CombatLogVersion::RETAIL_10_1_0, true],
            'unregistered newer retail'   => [22_012_009_009, true],
            'retail older than supported' => [CombatLogVersion::RETAIL_10_1_0 - 1, false],
            'shadowlands retail'          => [19_009_002_007, false],
        ];
    }

    #[Test]
    public function isRegistered_givenARegisteredAndAnUnregisteredVersion_returnsOnlyTheRegisteredOne(): void
    {
        // Act
        $registered   = CombatLogVersion::isRegistered(CombatLogVersion::RETAIL_12_1_0);
        $unregistered = CombatLogVersion::isRegistered(22_012_009_009);

        // Assert
        Assert::assertTrue($registered);
        Assert::assertFalse($unregistered);
    }

    #[Test]
    public function getLatestRetail_givenTheRegistry_returnsTheNewestRegisteredRetailVersion(): void
    {
        // Act
        $result = CombatLogVersion::getLatestRetail();

        // Assert
        Assert::assertSame(max(array_keys(CombatLogVersion::RETAIL_ALL)), $result);
    }
}
