<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Suffix;

use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\DamageInterface;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V20\DamageV20;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage\V22\DamageV22;
use App\Logic\CombatLog\CombatEvents\Suffixes\Suffix;
use App\Logic\CombatLog\CombatLogVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

class DamageTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('Suffix')]
    #[Group('Damage')]
    #[DataProvider('createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider')]
    public function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix(
        int    $combatLogVersion,
        string $expectedClassName,
    ): void {
        // Arrange

        // Act
        $suffix = Suffix::createFromEventName($combatLogVersion, 'DAMAGE');

        // Assert
        $this->assertInstanceOf($expectedClassName, $suffix);
        $this->assertInstanceOf(DamageInterface::class, $suffix);
    }

    public static function createFromEventName_givenCombatLogVersion_returnsCorrectSuffix_dataProvider(): array
    {
        return [
            [
                'combatLogVersion'  => CombatLogVersion::CLASSIC,
                'expectedClassName' => DamageV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_10_1_0,
                'expectedClassName' => DamageV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_2,
                'expectedClassName' => DamageV20::class,
            ],
            [
                'combatLogVersion'  => CombatLogVersion::RETAIL_11_0_5,
                'expectedClassName' => DamageV22::class,
            ],
        ];
    }
}
