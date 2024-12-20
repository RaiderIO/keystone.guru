<?php

namespace Tests\Unit\App\Logic\CombatLog\SpecialEvents\DamageSplit;

use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\SpecialEvents\DamageSplit;
use App\Logic\CombatLog\SpecialEvents\DamageSplit\DamageSplitInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

final class DamageSplitTest extends PublicTestCase
{
    #[Test]
    #[Group('CombatLog')]
    #[Group('DamageSplit')]
    #[DataProvider('parseEvent_ShouldReturnDamageSplitEvent_GivenDamageSplitEvent_DataProvider')]
    public function parseEvent_ShouldReturnDamageSplitEvent_GivenDamageSplitEvent(
        string $damageSplitEvent
    ): void {
        // Arrange
        $combatLogEntry = new CombatLogEntry($damageSplitEvent);

        // Act
        /** @var DamageSplitInterface $parseEventResult */
        $parseEventResult = $combatLogEntry->parseEvent();

        // Assert
        Assert::assertInstanceOf(DamageSplitInterface::class, $combatLogEntry->getParsedEvent());
    }

    public static function parseEvent_ShouldReturnDamageSplitEvent_GivenDamageSplitEvent_DataProvider(): array
    {
        return [
            [
                '11/9/2024 23:29:26.4921  DAMAGE_SPLIT,Player-1084-0B0A5965,"Wolflocks-TarrenMill-EU",0x512,0x0,Pet-0-4251-2662-13168-17252-0A03DFDE2B,"Haafaran",0x1112,0x0,108446,"Soul Link",0x20,Pet-0-4251-2662-13168-17252-0A03DFDE2B,Player-1084-0B0A5965,4720995,5410242,59318,90782,103612,0,0,10010,3,96,200,0,1523.53,812.65,2359,0.3029,610,0,0,-1,8,0,0,25283,nil,nil,nil,AOE',
            ],
            [
                '11/9/2024 23:31:29.4881  DAMAGE_SPLIT,Player-1084-0B0A5965,"Wolflocks-TarrenMill-EU",0x512,0x0,Pet-0-4251-2662-13168-17252-0B03DFDE2B,"Haafaran",0x1112,0x0,108446,"Soul Link",0x20,Pet-0-4251-2662-13168-17252-0B03DFDE2B,Player-1084-0B0A5965,4508536,4508536,59318,90782,103612,0,0,328769,3,76,200,0,1917.56,1491.88,2359,5.5034,610,0,0,-1,8,0,0,37476,nil,nil,nil,AOE',
            ],
        ];
    }
}
