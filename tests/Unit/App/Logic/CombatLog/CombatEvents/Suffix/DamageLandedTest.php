<?php

namespace App\Logic\CombatLog\CombatEvents\Suffix;

use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\DamageLandedInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V20\DamageLandedV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\DamageLanded\V22\DamageLandedV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class DamageLandedTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('DamageLanded')]
    #[DataProvider('createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider')]
    public function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix(
        int    $combatLogVersion,
        string $expectedClassName
    ): void {
        // Arrange


        // Act
        $suffix = Suffix::createFromEventName($combatLogVersion, 'DAMAGE_LANDED');

        // Assert
        $this->assertInstanceOf($expectedClassName, $suffix);
        $this->assertInstanceOf(DamageLandedInterface::class, $suffix);
    }

    public static function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider(): array
    {
        return [
            [
                'combatLogVersion'  => CombatLogVersion::CLASSIC,
                'expectedClassName' => DamageLandedV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_10_1_0,
                'expectedClassName' => DamageLandedV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_2,
                'expectedClassName' => DamageLandedV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_5,
                'expectedClassName' => DamageLandedV22::class,
            ],
        ];
    }
}
